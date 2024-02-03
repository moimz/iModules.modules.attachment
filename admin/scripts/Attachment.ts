/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 관리자 UI 이벤트를 관리하는 클래스를 정의한다.
 *
 * @file /modules/attachment/admin/scripts/Attachment.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 3.
 */
namespace modules {
    export namespace attachment {
        export namespace admin {
            export class Attachment extends modules.admin.admin.Component {
                /**
                 * 모듈 환경설정 폼을 가져온다.
                 *
                 * @return {Promise<Aui.Form.Panel>} configs
                 */
                async getConfigsForm(): Promise<Aui.Form.Panel> {
                    return new Aui.Form.Panel({
                        items: [
                            new Aui.Form.FieldSet({
                                title: (await this.getText('admin.configs.default')) as string,
                                items: [
                                    new AdminUi.Form.Field.Template({
                                        label: (await this.getText('admin.configs.template')) as string,
                                        name: 'template',
                                        componentType: this.getType(),
                                        componentName: this.getName(),
                                    }),
                                ],
                            }),
                            new Aui.Form.FieldSet({
                                title: (await this.getText('admin.configs.limits')) as string,
                                items: [
                                    new Aui.Form.Field.Container({
                                        label: (await this.getText('admin.configs.max_file_size')) as string,
                                        items: [
                                            new Aui.Form.Field.Number({
                                                name: 'max_file_size',
                                                width: 80,
                                            }),
                                            new Aui.Form.Field.Display({
                                                value: 'MB',
                                            }),
                                        ],
                                        helpText: (await this.getText('admin.configs.max_file_size_help')) as string,
                                    }),
                                    new Aui.Form.Field.Container({
                                        label: (await this.getText('admin.configs.max_upload_size')) as string,
                                        items: [
                                            new Aui.Form.Field.Number({
                                                name: 'max_upload_size',
                                                width: 80,
                                            }),
                                            new Aui.Form.Field.Display({
                                                value: 'MB',
                                            }),
                                        ],
                                        helpText: (await this.getText('admin.configs.max_upload_size_help')) as string,
                                    }),
                                ],
                            }),
                        ],
                    });
                }

                /**
                 * 임시파일관리
                 */
                drafts = {
                    /**
                     * 임시파일을 삭제한다.
                     */
                    delete: (): void => {
                        const drafts = Aui.getComponent('drafts') as Aui.Grid.Panel;
                        const draft_ids = [];
                        for (const draft of drafts.getSelections()) {
                            draft_ids.push(draft.get('draft_id'));
                        }

                        if (draft_ids.length == 0) {
                            return;
                        }

                        Aui.Message.delete({
                            url: this.getProcessUrl('draft'),
                            params: { draft_ids: draft_ids.join(',') },
                            message: this.printText('admin.drafts.actions.delete'),
                            handler: async (results) => {
                                if (results.success == true) {
                                    drafts.getStore().reload();
                                }
                            },
                        });
                    },
                };
            }
        }
    }
}
