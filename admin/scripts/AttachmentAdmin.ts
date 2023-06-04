/**
 * 이 파일은 아이모듈 관리자모듈의 일부입니다. (https://www.imodules.io)
 *
 * 관리자 UI 이벤트를 관리하는 클래스를 정의한다.
 *
 * @file /modules/attachment/admin/scripts/AttachmentAdmin.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 3. 28.
 */
namespace modules {
    export namespace attachment {
        export class AttachmentAdmin extends Admin.Interface {
            /**
             * 모듈 환경설정 폼을 가져온다.
             */
            async getConfigsForm(): Promise<Admin.Form.Panel> {
                return new Admin.Form.Panel({
                    items: [
                        new Admin.Form.FieldSet({
                            title: (await this.getText('admin/configs/default')) as string,
                            items: [
                                new Admin.Form.Field.Template({
                                    label: (await this.getText('admin/configs/template')) as string,
                                    name: 'template',
                                    componentType: this.getType(),
                                    componentName: this.getName(),
                                }),
                            ],
                        }),
                        new Admin.Form.FieldSet({
                            title: (await this.getText('admin/configs/limits')) as string,
                            items: [
                                new Admin.Form.Field.Container({
                                    label: (await this.getText('admin/configs/max_file_size')) as string,
                                    items: [
                                        new Admin.Form.Field.Number({
                                            name: 'max_file_size',
                                            width: 80,
                                        }),
                                        new Admin.Form.Field.Display({
                                            value: 'MB',
                                        }),
                                    ],
                                    helpText: (await this.getText('admin/configs/max_file_size_help')) as string,
                                }),
                                new Admin.Form.Field.Container({
                                    label: (await this.getText('admin/configs/max_upload_size')) as string,
                                    items: [
                                        new Admin.Form.Field.Number({
                                            name: 'max_upload_size',
                                            width: 80,
                                        }),
                                        new Admin.Form.Field.Display({
                                            value: 'MB',
                                        }),
                                    ],
                                    helpText: (await this.getText('admin/configs/max_upload_size_help')) as string,
                                }),
                            ],
                        }),
                    ],
                });
            }
        }
    }
}
