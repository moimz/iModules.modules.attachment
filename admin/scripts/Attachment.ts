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
                    /**
                     * 만료일이 경과한 모든 임시파일을 삭제한다.
                     */
                    deleteAll: (): void => {
                        Aui.Message.show({
                            title: Aui.getErrorText('CONFIRM'),
                            message: this.printText('admin.drafts.actions.delete_all'),
                            icon: Aui.Message.CONFIRM,
                            buttons: Aui.Message.DANGERCANCEL,
                            handler: (button) => {
                                if (button.action == 'ok') {
                                    Aui.Message.progress({
                                        method: 'DELETE',
                                        url: this.getProcessUrl('drafts'),
                                        message: this.printText('admin.drafts.actions.delete_all_start'),
                                        progress: (progress, results) => {
                                            if (results.end == true) {
                                                progress.setMessage(
                                                    this.printText('admin.drafts.actions.deleted_all', {
                                                        total: Format.number(results.total),
                                                    })
                                                );
                                            } else {
                                                progress.setMessage(
                                                    this.printText('admin.drafts.actions.delete_all_progress', {
                                                        current: Format.number(results.current),
                                                        total: Format.number(results.total),
                                                    })
                                                );
                                            }
                                        },
                                        handler: async (_button, results) => {
                                            if (results.success == true) {
                                                const drafts = Aui.getComponent('drafts') as Aui.Grid.Panel;
                                                await drafts.getStore().reload();
                                            }
                                        },
                                    });
                                } else {
                                    Aui.Message.close();
                                }
                            },
                        });
                    },
                };

                /**
                 * 쓰레기파일 처리
                 */
                trashes = {
                    /**
                     * 첨부파일 최상위폴더부터 모든 하위폴더를 검색하여 데이터베이스에 연결되어 있지 않은 파일을 찾는다.
                     */
                    search: (): void => {
                        Aui.Message.show({
                            title: Aui.getErrorText('CONFIRM'),
                            message: this.printText('admin.trashes.actions.search'),
                            icon: Aui.Message.CONFIRM,
                            buttons: Aui.Message.OKCANCEL,
                            handler: (button) => {
                                if (button.action == 'ok') {
                                    Aui.Message.progress({
                                        method: 'POST',
                                        url: this.getProcessUrl('trashes'),
                                        message: this.printText('admin.trashes.actions.search_start'),
                                        progress: (progress, results) => {
                                            if (results.end == true) {
                                                progress.setMessage(
                                                    this.printText('admin.trashes.actions.searched', {
                                                        total: Format.number(results.total),
                                                    })
                                                );
                                            } else {
                                                progress.setMessage(
                                                    this.printText('admin.trashes.actions.search_progress', {
                                                        current: Format.number(results.current),
                                                        total: Format.number(results.total),
                                                        folder: results.data?.folder ?? '/',
                                                        files: results.data?.files.toString() ?? '0',
                                                    })
                                                );
                                            }
                                        },
                                        handler: async (_button, results) => {
                                            if (results.success == true) {
                                                const drafts = Aui.getComponent('trashes') as Aui.Grid.Panel;
                                                await drafts.getStore().reload();
                                            }
                                        },
                                    });
                                } else {
                                    Aui.Message.close();
                                }
                            },
                        });
                    },
                    /**
                     * 쓰레기파일을 삭제한다.
                     */
                    delete: (): void => {
                        const trashes = Aui.getComponent('trashes') as Aui.Grid.Panel;
                        const paths = [];
                        for (const trash of trashes.getSelections()) {
                            paths.push(trash.get('path'));
                        }

                        if (paths.length == 0) {
                            return;
                        }

                        Aui.Message.delete({
                            url: this.getProcessUrl('trash'),
                            params: { paths: paths.join(',') },
                            message: this.printText('admin.trashes.actions.delete'),
                            handler: async (results) => {
                                if (results.success == true) {
                                    trashes.getStore().reload();
                                }
                            },
                        });
                    },
                    /**
                     * 검색된 모든 쓰레기파일을 삭제한다.
                     */
                    deleteAll: (): void => {
                        Aui.Message.show({
                            title: Aui.getErrorText('CONFIRM'),
                            message: this.printText('admin.trashes.actions.delete_all'),
                            icon: Aui.Message.CONFIRM,
                            buttons: Aui.Message.DANGERCANCEL,
                            handler: (button) => {
                                if (button.action == 'ok') {
                                    Aui.Message.progress({
                                        method: 'DELETE',
                                        url: this.getProcessUrl('trashes'),
                                        message: this.printText('admin.trashes.actions.delete_all_start'),
                                        progress: (progress, results) => {
                                            if (results.end == true) {
                                                progress.setMessage(
                                                    this.printText('admin.trashes.actions.deleted_all', {
                                                        total: Format.number(results.total),
                                                    })
                                                );
                                            } else {
                                                progress.setMessage(
                                                    this.printText('admin.trashes.actions.delete_all_progress', {
                                                        current: Format.number(results.current),
                                                        total: Format.number(results.total),
                                                    })
                                                );
                                            }
                                        },
                                        handler: async (_button, results) => {
                                            if (results.success == true) {
                                                const trashes = Aui.getComponent('trashes') as Aui.Grid.Panel;
                                                await trashes.getStore().reload();
                                            }
                                        },
                                    });
                                } else {
                                    Aui.Message.close();
                                }
                            },
                        });
                    },
                };
            }
        }
    }
}
