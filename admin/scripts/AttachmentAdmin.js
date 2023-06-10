/**
 * 이 파일은 아이모듈 관리자모듈의 일부입니다. (https://www.imodules.io)
 *
 * 관리자 UI 이벤트를 관리하는 클래스를 정의한다.
 *
 * @file /modules/attachment/admin/scripts/AttachmentAdmin.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2023. 6. 10.
 */
var modules;
(function (modules) {
    let attachment;
    (function (attachment) {
        class AttachmentAdmin extends Admin.Interface {
            /**
             * 모듈 환경설정 폼을 가져온다.
             *
             * @return {Promise<Admin.Form.Panel>} configs
             */
            async getConfigsForm() {
                return new Admin.Form.Panel({
                    items: [
                        new Admin.Form.FieldSet({
                            title: (await this.getText('admin.configs.default')),
                            items: [
                                new Admin.Form.Field.Template({
                                    label: (await this.getText('admin.configs.template')),
                                    name: 'template',
                                    componentType: this.getType(),
                                    componentName: this.getName(),
                                }),
                            ],
                        }),
                        new Admin.Form.FieldSet({
                            title: (await this.getText('admin.configs.limits')),
                            items: [
                                new Admin.Form.Field.Container({
                                    label: (await this.getText('admin.configs.max_file_size')),
                                    items: [
                                        new Admin.Form.Field.Number({
                                            name: 'max_file_size',
                                            width: 80,
                                        }),
                                        new Admin.Form.Field.Display({
                                            value: 'MB',
                                        }),
                                    ],
                                    helpText: (await this.getText('admin.configs.max_file_size_help')),
                                }),
                                new Admin.Form.Field.Container({
                                    label: (await this.getText('admin.configs.max_upload_size')),
                                    items: [
                                        new Admin.Form.Field.Number({
                                            name: 'max_upload_size',
                                            width: 80,
                                        }),
                                        new Admin.Form.Field.Display({
                                            value: 'MB',
                                        }),
                                    ],
                                    helpText: (await this.getText('admin.configs.max_upload_size_help')),
                                }),
                            ],
                        }),
                    ],
                });
            }
        }
        attachment.AttachmentAdmin = AttachmentAdmin;
    })(attachment = modules.attachment || (modules.attachment = {}));
})(modules || (modules = {}));
