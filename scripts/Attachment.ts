/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일모듈 클래스를 정의한다.
 *
 * @file /modules/attachment/scripts/Attachment.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 4. 10.
 */
namespace modules {
    export namespace attachment {
        export class Attachment extends Module {
            static Uploaders: WeakMap<Dom, modules.attachment.Uploader> = new WeakMap();

            set($dom: Dom, properties: modules.attachment.Uploader.Properties): modules.attachment.Uploader {
                if (modules.attachment.Attachment.Uploaders.has($dom) == true) {
                    return modules.attachment.Attachment.Uploaders.get($dom);
                } else {
                    const uploader = new modules.attachment.Uploader($dom, this, properties);
                    modules.attachment.Attachment.Uploaders.set($dom, uploader);
                    return uploader;
                }
            }
        }

        /**
         * 업로더 클래스를 정의한다.
         */
        export namespace Uploader {
            export interface Properties {
                url: string;
            }

            export interface File {
                index: number;
                hash: string;
                name: string;
                mime: string;
                extension: string;
                size: number;
                type: string;
                status: string;
                upload: string;
                uploaded: number;
                view: string;
                download: string;
                thumbnail: string;
                file?: globalThis.File;
            }
        }

        export class Uploader {
            $dom: Dom;
            attachment: modules.attachment.Attachment;
            $input: Dom;
            index: number = 0;
            files: modules.attachment.Uploader.File[] = [];
            count: { uploaded: number; total: number } = { uploaded: 0, total: 0 };
            size: { uploaded: number; total: number } = { uploaded: 0, total: 0 };
            uploading: boolean = false;

            constructor(
                $dom: Dom,
                attachment: modules.attachment.Attachment,
                properties: modules.attachment.Uploader.Properties
            ) {
                this.$dom = $dom;
                this.attachment = attachment;

                this.$dom.append(this.$getInput());
                this.setEvent();
            }

            /**
             * FILE INPUT DOM 을 가져온다.
             *
             * @return {Dom} $input
             */
            $getInput(): Dom {
                if (this.$input === undefined) {
                    this.$input = Html.create('input', { type: 'file' });
                }

                return this.$input;
            }

            /**
             * 파일순서를 가져온다.
             *
             * @returns {number} index
             */
            getIndex(): number {
                return ++this.index;
            }

            /**
             * 파일종류를 가져온다.
             *
             * @param {string} mime - 파일 MIME
             * @returns {string} type - 파일종류
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
                        if (
                            detail.search(/(pdf|officedocument|opendocument|word|powerpoint|excel|json|xml|rtf)/) > -1
                        ) {
                            return 'document';
                        }

                        if (detail.search(/(zip|rar|tar|compressed)/) > -1) {
                            return 'archive';
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
             * @returns {string} extension - 확장자
             */
            getExtension(name: string): string {
                return name.split('.').pop().toLowerCase();
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

                    const draft: modules.attachment.Uploader.File = {
                        index: this.getIndex(),
                        hash: null,
                        name: Format.normalizer(file.name),
                        mime: file.type,
                        extension: this.getExtension(file.name),
                        size: file.size,
                        type: this.getType(file.type),
                        status: 'DRAFT',
                        uploaded: 0,
                        view: null,
                        upload: null,
                        download: null,
                        thumbnail: null,
                        file: file,
                    };

                    if (['image', 'svg', 'icon'].includes(draft.type) !== false) {
                        draft.thumbnail = URL.createObjectURL(draft.file);
                    }

                    this.files.push(draft);
                    this.#print(draft);
                }

                this.#updateFiles();

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
                    size.total += file.size;
                }

                this.count = count;
                this.size = size;
            }

            /**
             * 다음에 업로드할 파일을 가져온다.
             *
             * @return {modules.attachment.Uploader.File} file
             */
            #getNext(): modules.attachment.Uploader.File {
                for (const file of this.files) {
                    if (file.status == 'DRAFT' || file.status == 'UPLOADING') {
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
             * 업로드를 시작한다.
             */
            start(): void {
                if (this.uploading === true) {
                    return;
                }

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

                if (file.upload === null) {
                    const results = await Ajax.post(this.attachment.getProcessUrl('draft'), {
                        name: file.name,
                        size: file.size,
                    });

                    if (results.success == true) {
                        file.upload = results.upload;
                    } else {
                        return;
                    }
                }

                this.#updateFiles();

                const chunkSize = 5 * 1000 * 1000; // 5MB
                const chunk = file.size > file.uploaded + chunkSize ? file.uploaded + chunkSize : file.size;

                const request = new XMLHttpRequest();
                request.responseType = 'json';
                request.open('POST', file.upload, true);
                request.setRequestHeader('Content-Type', 'application/octet-stream');
                request.setRequestHeader('Accept-Language', iModules.getLanguage());
                request.setRequestHeader(
                    'Content-Range',
                    'bytes ' + file.uploaded + '-' + (chunk - 1) + '/' + file.size
                );
                request.upload.addEventListener('progress', (e: ProgressEvent) => {
                    this.#updateProgress(file, e.loaded);
                });
                request.addEventListener('load', () => {
                    const results = request.response;

                    if (results.success == true) {
                        file.status = results.status;
                        file.uploaded = results.uploaded;

                        this.#upload();
                    }
                });
                request.send(file.file.slice(file.uploaded, chunk));
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
                const $icon = Html.create('i', { 'data-type': file.type, 'data-extension': file.extension });
                $icon.addClass('icon');
                $preview.append($icon);
                if (file.thumbnail !== null) {
                    const $image = Html.create('div');
                    $image.addClass('image');
                    $image.setStyle('background-image', 'url(' + file.thumbnail + ')');
                    $preview.append($image);
                }
                $item.append($preview);

                const $label = Html.create('label', { 'data-role': 'name' });
                const $name = Html.create('b');
                $name.html(file.name);
                $label.append($name);
                $item.append($label);

                const $size = Html.create('small', { 'data-role': 'size' });
                $size.html(Format.size(file.size));
                $item.append($size);

                $file.append($item);

                const $origin = Html.get('li[data-index="' + file.index + '"]', $files);
                if ($origin.getEl() === null) {
                    $files.append($file);
                } else {
                    $origin.replaceWith($file);
                }

                if (file.name.length >= 8) {
                    let length = file.name.length - 6;
                    while ($label.getOuterHeight() < $name.getOuterHeight()) {
                        if (length <= 0) {
                            break;
                        }

                        $name.html(Format.substring(file.name, [length, 6]));
                        --length;
                    }
                }
            }

            /**
             * 프로그래스바를 업데이트한다.
             *
             * @param {modules.attachment.Uploader.File} file - 현재 업로드 중인 파일
             * @param {number} uploaded - 현재 업로드세션에서 업로드된 용량
             */
            #updateProgress(file: modules.attachment.Uploader.File, uploaded: number): void {
                console.log('progress', file.uploaded + uploaded, file.size);
                //
            }

            /**
             * 업로드를 완료처리한다.
             */
            #complete(): void {
                console.log('complete!');
            }

            /**
             * 이벤트를 등록한다.
             */
            setEvent(): void {
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
        }
    }
}
