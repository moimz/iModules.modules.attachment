/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일모듈 클래스를 정의한다.
 *
 * @file /modules/attachment/scripts/Attachment.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 6. 10.
 */
namespace modules {
    export namespace attachment {
        export class Attachment extends Module {
            static Uploaders: WeakMap<HTMLElement, modules.attachment.Uploader> = new WeakMap();

            /**
             * 업로더를 설정한다.
             *
             * @param {Dom} $dom - 업로더가 위치한 DOM 객체
             * @param {modules.attachment.Uploader.Properties} properties - 업로더 설정
             * @return {modules.attachment.Uploader} uploader - 업로더 객체
             */
            set($dom: Dom, properties: modules.attachment.Uploader.Properties): modules.attachment.Uploader {
                if (modules.attachment.Attachment.Uploaders.has($dom.getEl()) == true) {
                    return modules.attachment.Attachment.Uploaders.get($dom.getEl());
                } else {
                    const uploader = new modules.attachment.Uploader($dom, properties);
                    modules.attachment.Attachment.Uploaders.set($dom.getEl(), uploader);
                    return uploader;
                }
            }

            /**
             * 첨부파일 정보를 가져온다.
             *
             * @param {string} id - 첨부파일고유값
             * @return {Promise<modules.attachment.File>} file - 첨부파일
             */
            async getAttachment(id: string): Promise<modules.attachment.File> {
                const results = await Ajax.get(this.getProcessUrl('attachment'), { id: id });
                if (results.success == true && results.attachment) {
                    return results.attachment;
                }

                return null;
            }
        }

        /**
         * 첨부파일에서 사용하는 File 구조체를 정의한다.
         */
        export interface File {
            id: string;
            name: string;
            type: string;
            mime: string;
            extension: string;
            size: number;
            view: string;
            download: string;
            thumbnail: string;
        }

        /**
         * 업로더 클래스를 정의한다.
         */
        export namespace Uploader {
            export interface Properties {
                /**
                 * @type {string} accept - 업로드 허용파일
                 */
                accept?: string;

                /**
                 * @type {boolean} multiple - 다중파일 선택여부
                 */
                multiple?: boolean;

                /**
                 * @type {Object} listeners - 이벤트리스너
                 */
                listeners?: { [name: string]: Function };
            }

            /**
             * 업로더에서 사용하는 File 구조체를 정의한다.
             */
            export interface File {
                index: number;
                status: string;
                upload: string;
                uploaded: number;
                attachment: modules.attachment.File;
                file?: globalThis.File;
            }
        }

        export class Uploader {
            $dom: Dom;
            $input: Dom;
            index: number = 0;
            files: modules.attachment.Uploader.File[] = [];
            count: { uploaded: number; total: number } = { uploaded: 0, total: 0 };
            size: { uploaded: number; total: number } = { uploaded: 0, total: 0 };
            uploading: boolean = false;
            request: XMLHttpRequest = null;
            accept: string;
            multiple: boolean;
            listeners: { [name: string]: Function[] } = {};

            /**
             * 업로더 클래스를 정의한다.
             *
             * @param {Dom} $dom - 업로더 DOM 객체
             * @param {modules.attachment.Uploader.Properties} properties - 설정
             */
            constructor($dom: Dom, properties: modules.attachment.Uploader.Properties) {
                this.$dom = $dom;

                this.accept = properties.accept ?? '*';
                this.multiple = properties.multiple !== false;

                if (properties.listeners !== undefined) {
                    for (const name in properties.listeners) {
                        this.addEvent(name, properties.listeners[name]);
                    }
                }

                this.$dom.append(this.$getInput());

                this.$getInput().on('change', (e: Event & { target: HTMLInputElement }) => {
                    this.#add(e.target.files);
                });
                const $button = Html.get('button[data-action=select]', this.$dom);
                if ($button.getEl() !== null) {
                    $button.on('click', () => {
                        this.select();
                    });
                }
            }

            /**
             * FILE INPUT DOM 을 가져온다.
             *
             * @return {Dom} $input
             */
            $getInput(): Dom {
                if (this.$input === undefined) {
                    this.$input = Html.create('input', { type: 'file', accept: this.accept });
                    if (this.multiple == true) {
                        this.$input.setAttr('multiple', 'multiple');
                    }
                }

                return this.$input;
            }

            /**
             * 파일순서를 가져온다.
             *
             * @return {number} index
             */
            getIndex(): number {
                return ++this.index;
            }

            /**
             * 파일종류를 가져온다.
             *
             * @param {string} mime - 파일 MIME
             * @return {string} type - 파일종류
             */
            getType(mime: string): string {
                const types = mime.match(/^(.*?)\/(.*?)$/);
                if (types === null) {
                    return 'file';
                }

                const type = types[1];
                const detail = types[2];

                switch (type) {
                    case 'image':
                        if (detail.search(/svg/) > -1) {
                            return 'svg';
                        } else if (detail == 'x-icon') {
                            return 'icon';
                        }

                        return 'image';

                    case 'application':
                        if (detail.search(/(pdf|officedocument|opendocument|word|powerpoint|excel|xml|rtf)/) > -1) {
                            return 'document';
                        } else if (detail.search(/(zip|rar|tar|compressed)/) > -1) {
                            return 'archive';
                        } else if (detail.search(/(json)/) > -1) {
                            return 'text';
                        }

                        return 'file';

                    case 'video':
                    case 'audio':
                    case 'text':
                        return type;
                }

                return 'file';
            }

            /**
             * 파일 확장자를 가져온다.
             *
             * @param {string} name - 파일명
             * @return {string} extension - 확장자
             */
            getExtension(name: string): string {
                const extension = name.split('.').pop().toLowerCase();
                const replacement = {
                    'jpeg': 'jpg',
                    'htm': 'html',
                };
                return replacement[extension] ?? extension;
            }

            /**
             * 선택된 파일을 대기열에 추가한다.
             *
             * @param {FileList} files - 추가할 파일객체
             */
            #add(files: FileList): void {
                for (const file of files) {
                    if (file.size == 0) {
                        continue;
                    }

                    if (this.multiple === false) {
                        while (this.files.length > 0) {
                            this.#remove(this.files.shift());
                        }
                    }

                    const draft: modules.attachment.Uploader.File = {
                        index: this.getIndex(),
                        status: 'WAITING',
                        upload: null,
                        uploaded: 0,
                        attachment: {
                            id: null,
                            name: Format.normalizer(file.name),
                            type: this.getType(file.type),
                            mime: file.type,
                            extension: this.getExtension(file.name),
                            size: file.size,
                            view: null,
                            download: null,
                            thumbnail: null,
                        },
                        file: file,
                    };

                    if (['image', 'svg', 'icon'].includes(draft.attachment.type) !== false) {
                        draft.attachment.thumbnail = URL.createObjectURL(draft.file);
                    }

                    this.files.push(draft);
                    this.#print(draft);

                    this.fireEvent('add', [draft, this]);
                }

                this.#updateFiles();

                this.$input.reset();

                if (this.count.total > this.count.uploaded) {
                    this.start();
                }
            }

            /**
             * 파일목록의 수치를 갱신한다.
             */
            #updateFiles(): void {
                const count = { uploaded: 0, total: 0 };
                const size = { uploaded: 0, total: 0 };
                for (const file of this.files) {
                    if (file.status == 'COMPLETE') {
                        count.uploaded++;
                        size.uploaded += file.uploaded;
                    }
                    count.total++;
                    size.total += file.attachment.size;
                }

                this.count = count;
                this.size = size;
            }

            /**
             * 파일을 제거한다.
             *
             * @param {modules.attachment.Uploader.File} file
             */
            #remove(file: modules.attachment.Uploader.File): void {
                if (file.status == 'UPLOADING') {
                    this.request?.abort();
                }
                this.files.splice(this.files.indexOf(file), 1);
                Html.get('ul[data-role=files] > li[data-index="' + file.index.toString() + '"]', this.$dom).remove();

                this.#updateFiles();
                this.#upload();
            }

            /**
             * 다음에 업로드할 파일을 가져온다.
             *
             * @return {modules.attachment.Uploader.File} file
             */
            #getNext(): modules.attachment.Uploader.File {
                for (const file of this.files) {
                    if (file.status == 'WAITING' || file.status == 'UPLOADING' || file.status == 'LOADING') {
                        return file;
                    }
                }

                return null;
            }

            /**
             * 파일을 선택한다.
             */
            select(): void {
                this.$getInput().getEl().click();
            }

            /**
             * 파일목록에서 파일을 제거한다.
             *
             * @param {number} index - 인덱스
             */
            remove(index: number): void {
                const file = this.getFile(index);
                if (file !== null) {
                    this.#remove(file);
                }
            }

            /**
             * 파일목록에서 파일을 제거한다.
             *
             * @param {number} id - 파일고유값
             */
            removeById(id: string): void {
                const file = this.getFileById(id);
                if (file !== null) {
                    this.#remove(file);
                }
            }

            /**
             * 업로드가 완료된 파일의 고유값을 가져온다.
             *
             * @return {string[]} ids - 첨부파일고유값
             */
            getValue(): string[] {
                const ids: string[] = [];
                for (const file of this.files) {
                    if (file.status == 'COMPLETE') {
                        ids.push(file.attachment.id);
                    }
                }
                return ids;
            }

            /**
             * 기존에 업로드된 첨부파일 고유값을 설정한다.
             *
             * @param {string[]} ids - 첨부파일고유값
             */
            setValue(ids: string[]): void {
                if (Array.isArray(ids) === false) {
                    return;
                }

                if (Format.isEqual(this.getValue(), ids) !== true) {
                    for (const file of this.files) {
                        this.#remove(file);
                    }

                    for (const id of ids) {
                        const file: modules.attachment.Uploader.File = {
                            index: this.getIndex(),
                            status: 'LOADING',
                            upload: null,
                            uploaded: 0,
                            attachment: {
                                id: id,
                                name: 'Loading...',
                                type: 'file',
                                mime: '',
                                extension: '',
                                size: 0,
                                view: null,
                                download: null,
                                thumbnail: null,
                            },
                        };
                        this.files.push(file);
                        this.#print(file);
                    }

                    this.start();
                }
            }

            /**
             * 현재 첨부파일 목록에서 특정 인덱스를 가진 파일객체를 가져온다.
             *
             * @param {string} index - 인덱스
             * @return {modules.attachment.Uploader.File} file
             */
            getFile(index: number): modules.attachment.Uploader.File {
                for (const file of this.files) {
                    if (file.index === index) {
                        return file;
                    }
                }

                return null;
            }

            /**
             * 현재 첨부파일 목록에 특정 id 를 가진 파일객체를 가져온다.
             *
             * @param {string} id - 첨부파일고유값
             * @return {modules.attachment.Uploader.File} file
             */
            getFileById(id: string): modules.attachment.Uploader.File {
                for (const file of this.files) {
                    if (file.attachment.id === id) {
                        return file;
                    }
                }

                return null;
            }

            /**
             * 현재 첨부파일 목록에 특정 id 가 있는지 찾는다.
             *
             * @param {string} id - 검색할 첨부파일 고유값
             * @return {boolean} hasValue
             */
            hasValue(id: string): boolean {
                return this.getFileById(id) !== null;
            }

            /**
             * 업로드를 시작한다.
             */
            start(): void {
                if (this.uploading === true) {
                    return;
                }

                this.fireEvent('start', [this]);
                this.uploading = true;
                this.#upload();
            }

            /**
             * 업로드를 진행한다.
             */
            async #upload(): Promise<void> {
                const file = this.#getNext();
                if (file === null) {
                    this.#complete();
                    return;
                }

                if (file.status == 'LOADING') {
                    const mAttachment = Modules.get('attachment') as modules.attachment.Attachment;
                    const attachment = await mAttachment.getAttachment(file.attachment.id);
                    if (attachment === null) {
                        this.remove(file.index);
                    } else {
                        file.status = 'COMPLETE';
                        file.attachment = attachment;
                        this.fireEvent('uploaded', [file, this]);
                        this.#update(file);
                    }

                    this.#upload();

                    return;
                }

                if (file.upload === null) {
                    const results = await Ajax.post(Modules.get('attachment').getProcessUrl('draft'), file.attachment);
                    if (results.success == true) {
                        file.status = 'UPLOADING';
                        file.upload = results.upload;
                        this.#update(file);
                    } else {
                        return;
                    }
                }

                this.#updateFiles();

                const chunkSize = 5 * 1000 * 1000; // 5MB
                const chunk =
                    file.attachment.size > file.uploaded + chunkSize ? file.uploaded + chunkSize : file.attachment.size;

                this.request = new XMLHttpRequest();
                this.request.responseType = 'json';
                this.request.open('POST', file.upload, true);
                this.request.setRequestHeader('Content-Type', 'application/octet-stream');
                this.request.setRequestHeader('Accept-Language', iModules.getLanguage());
                this.request.setRequestHeader(
                    'Content-Range',
                    'bytes ' + file.uploaded + '-' + (chunk - 1) + '/' + file.attachment.size
                );
                this.request.upload.addEventListener('progress', (e: ProgressEvent) => {
                    this.#updateProgress(file, e.loaded);
                });
                this.request.addEventListener('load', () => {
                    const results = this.request.response;

                    if (results.success == true) {
                        file.status = results.status;
                        file.uploaded = results.uploaded;

                        if (results.status == 'COMPLETE') {
                            file.attachment = results.attachment;
                            this.fireEvent('uploaded', [file, this]);
                            this.#update(file);
                        }

                        this.#upload();
                    } else {
                        // @todo FAIL
                    }
                });
                this.request.addEventListener('abort', () => {
                    console.log('abort');
                });
                this.request.send(file.file.slice(file.uploaded, chunk));
            }

            /**
             * 파일을 출력한다.
             *
             * @param {modules.attachment.Uploader.File} file
             */
            #print(file: modules.attachment.Uploader.File): void {
                const $files = Html.get('ul[data-role=files]', this.$dom);
                if ($files.getEl() === null) {
                    return;
                }

                const $file = Html.create('li', { 'data-index': file.index.toString() });

                const $item = Html.create('div', { 'data-module': 'attachment', 'data-role': 'file' });
                $item.setData('status', file.status);

                const $preview = Html.create('div', { 'data-role': 'preview' });
                const $icon = Html.create('i', {
                    'data-type': file.attachment.type,
                    'data-extension': file.attachment.extension,
                });
                $preview.append($icon);
                if (file.attachment.thumbnail !== null) {
                    const $image = Html.create('div');
                    $image.setStyle('background-image', 'url(' + file.attachment.thumbnail + ')');
                    if (file.attachment.thumbnail.indexOf('blob') === 0) {
                        $image.setData('blob', file.attachment.thumbnail, false);
                    }
                    $preview.append($image);
                }

                const $progress = Html.create('progress', {
                    min: '0',
                    max: '100',
                    value: '0',
                });
                $preview.append($progress);

                $item.append($preview);

                const $label = Html.create('label', { 'data-role': 'name' });
                const $name = Html.create('b');
                $name.html(file.attachment.name);
                $label.append($name);
                $item.append($label);

                const $size = Html.create('small', { 'data-role': 'size' });
                $size.html(Format.size(file.attachment.size));
                $item.append($size);

                const $download = Html.create('a', { 'data-action': 'download' });
                $download.html('<i></i><span> ' + Modules.get('attachment').printText('buttons.download') + '</span>');
                $item.append($download);

                const $delete = Html.create('button', { type: 'button', 'data-action': 'delete' });
                $delete.html('<i></i><span> ' + Modules.get('attachment').printText('buttons.delete') + '</span>');
                $delete.on('click', () => {
                    this.remove(file.index);
                });
                $item.append($delete);

                $file.append($item);

                const $origin = Html.get('li[data-index="' + file.index + '"]', $files);
                if ($origin.getEl() === null) {
                    $files.append($file);
                } else {
                    $origin.replaceWith($file);
                }

                if (file.attachment.name.length >= 8) {
                    let length = file.attachment.name.length - 6;
                    while ($label.getOuterHeight() < $name.getOuterHeight()) {
                        if (length <= 0) {
                            break;
                        }

                        $name.html(Format.substring(file.attachment.name, [length, 6]));
                        --length;
                    }
                }
            }

            /**
             * 파일을 갱신한다.
             *
             * @param {modules.attachment.Uploader.File} file
             */
            #update(file: modules.attachment.Uploader.File): void {
                const $file = Html.get(
                    'ul[data-role=files] > li[data-index="' + file.index.toString() + '"]',
                    this.$dom
                );
                if ($file.getEl() === null) {
                    return;
                }

                const $item = Html.get('div[data-module=attachment][data-role=file]', $file);
                if ($item.getEl() === null) {
                    return;
                }

                $item.setData('status', file.status);

                const $preview = Html.get('div[data-role=preview]', $item);
                const $icon = Html.get('i', $preview);
                $icon.setData('type', file.attachment.type);
                $icon.setData('extension', file.attachment.extension);

                if (file.attachment.thumbnail !== null) {
                    const $image = Html.get('div', $preview);
                    if ($image.getEl() !== null) {
                        if ($image.getData('blob') !== null) {
                            URL.revokeObjectURL($image.getData('blob'));
                        }
                        $image.setStyle('background-image', 'url(' + file.attachment.thumbnail + ')');
                    } else {
                        const $image = Html.create('div');
                        $image.setStyle('background-image', 'url(' + file.attachment.thumbnail + ')');
                        $preview.append($image);
                    }
                }

                const $label = Html.get('label[data-role=name]', $item);
                const $name = Html.get('b', $label);
                $name.html(file.attachment.name);

                const $size = Html.get('small[data-role=size]', $item);
                $size.html(Format.size(file.attachment.size));

                const $download = Html.get('a[data-action=download]', $item);
                $download.setAttr('href', file.attachment.download);
                $download.setAttr('download', file.attachment.name);

                let length = file.attachment.name.length - 6;
                while ($label.getOuterHeight() < $name.getOuterHeight()) {
                    if (length <= 0) {
                        break;
                    }

                    $name.html(Format.substring(file.attachment.name, [length, 6]));
                    --length;
                }
            }

            /**
             * 프로그래스바를 업데이트한다.
             *
             * @param {modules.attachment.Uploader.File} file - 현재 업로드 중인 파일
             * @param {number} uploaded - 현재 업로드세션에서 업로드된 용량
             */
            #updateProgress(file: modules.attachment.Uploader.File, uploaded: number): void {
                const $file = Html.get(
                    'ul[data-role=files] > li[data-index="' + file.index.toString() + '"]',
                    this.$dom
                );
                if ($file.getEl() !== null) {
                    const $progress = Html.get('progress', $file);
                    if ($progress.getEl() !== null) {
                        $progress.setAttr(
                            'value',
                            (((file.uploaded + uploaded) / file.attachment.size) * 100).toFixed(2)
                        );
                    }
                    return;
                }
            }

            /**
             * 업로드를 완료처리한다.
             */
            #complete(): void {
                this.uploading = false;
                this.fireEvent('complete', [this]);
            }

            /**
             * 이벤트리스너를 등록한다.
             *
             * @param {string} name - 이벤트명
             * @param {Function} listener - 이벤트리스너
             */
            addEvent(name: string, listener: Function): void {
                if (this.listeners[name] == undefined) {
                    this.listeners[name] = [];
                }

                this.listeners[name].push(listener);
            }

            /**
             * 이벤트를 발생시킨다.
             *
             * @param {string} name - 이벤트명
             * @param {any[]} params - 이벤트리스너에 전달될 데이터
             * @return {boolean} result
             */
            fireEvent(name: string, params: any[] = []): boolean {
                if (this.listeners[name] !== undefined) {
                    for (const listener of this.listeners[name]) {
                        if (listener(...params) === false) {
                            return false;
                        }
                    }
                }

                return true;
            }
        }
    }
}
