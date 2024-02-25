/**
 * 이 파일은 아이모듈 첨부파일모듈의 일부입니다. (https://www.imodules.io)
 *
 * 첨부파일관리 화면을 구성한다.
 *
 * @file /modules/member/admin/scripts/attachments.ts
 * @author Arzz <arzz@arzz.com>
 * @license MIT License
 * @modified 2024. 2. 25.
 */
Admin.ready(async () => {
    const me = Admin.getModule('attachment');
    return new Aui.Tab.Panel({
        id: 'attachments-context',
        iconClass: 'xi xi-upload',
        title: (await me.getText('admin.contexts.attachments')),
        border: false,
        layout: 'fit',
        items: [
            new Aui.Grid.Panel({
                id: 'attachments',
                iconClass: 'xi xi-upload',
                title: (await me.getText('admin.attachments.title')),
                selection: { selectable: true, display: 'check' },
                autoLoad: false,
                border: false,
                layout: 'fit',
                topbar: [
                    new Aui.Form.Field.Search({
                        width: 200,
                        emptyText: (await me.getText('keyword')),
                        handler: async () => {
                            //
                        },
                    }),
                ],
                bottombar: new Aui.Grid.Pagination([
                    new Aui.Button({
                        iconClass: 'mi mi-refresh',
                        handler: (button) => {
                            const grid = button.getParent().getParent();
                            grid.getStore().reload();
                        },
                    }),
                ]),
                store: new Aui.Store.Remote({
                    url: me.getProcessUrl('attachments'),
                    fields: [
                        { name: 'created_at', type: 'int' },
                        { name: 'size', type: 'int' },
                    ],
                    primaryKeys: ['attachment_id'],
                    limit: 50,
                    remoteSort: true,
                    sorters: { created_at: 'DESC' },
                }),
                columns: [
                    {
                        text: (await me.getText('admin.attachments.attachment_id')),
                        dataIndex: 'attachment_id',
                        sortable: true,
                        width: 275,
                        textClass: 'monospace small',
                    },
                    {
                        text: (await me.getText('admin.attachments.name')),
                        dataIndex: 'name',
                        sortable: true,
                        width: 180,
                        renderer: (value, record) => {
                            return ('<i class="icon" style="background-image:url(' + record.get('icon') + ')"></i>' + value);
                        },
                    },
                    {
                        text: (await me.getText('admin.attachments.component')),
                        dataIndex: 'component_title',
                        width: 140,
                        renderer: (value, record) => {
                            if (value === null) {
                                return '';
                            }
                            return record.get('component_icon') + value;
                        },
                    },
                    {
                        text: (await me.getText('admin.attachments.position')),
                        dataIndex: 'position_type',
                        width: 200,
                        textClass: 'small',
                        renderer: (value, record) => {
                            if (value === null) {
                                return '';
                            }
                            return value + ' (' + record.get('position_id') + ')';
                        },
                    },
                    {
                        text: (await me.getText('admin.attachments.path')),
                        dataIndex: 'path',
                        minWidth: 240,
                        flex: 1,
                        textClass: 'monospace small',
                    },
                    {
                        text: (await me.getText('admin.attachments.size')),
                        dataIndex: 'size',
                        sortable: true,
                        width: 90,
                        textClass: 'numeric small',
                        renderer: (value) => {
                            return Format.size(value);
                        },
                    },
                ],
                listeners: {
                    openItem: (record) => {
                        // @todo 파일 다운로드
                        console.log(record);
                    },
                    openMenu: (menu, record) => {
                        menu.setTitle(record.get('name'));
                    },
                    openMenus: (menu, selections) => {
                        menu.setTitle(Aui.printText('texts.selected_item', {
                            count: selections.length.toString(),
                        }));
                        // @todo 일괄 다운로드
                    },
                },
            }),
            new Aui.Grid.Panel({
                id: 'drafts',
                iconClass: 'xi xi-marquee-add',
                title: (await me.getText('admin.drafts.title')),
                selection: { selectable: true, display: 'check' },
                autoLoad: false,
                border: false,
                layout: 'fit',
                topbar: [
                    new Aui.Form.Field.Search({
                        width: 200,
                        emptyText: (await me.getText('keyword')),
                        handler: async () => {
                            //
                        },
                    }),
                    '->',
                    new Aui.Button({
                        iconClass: 'mi mi-trash',
                        text: (await me.getText('admin.drafts.delete_all')),
                        handler: () => {
                            me.drafts.deleteAll();
                        },
                    }),
                ],
                bottombar: new Aui.Grid.Pagination([
                    new Aui.Button({
                        iconClass: 'mi mi-refresh',
                        handler: (button) => {
                            const grid = button.getParent().getParent();
                            grid.getStore().reload();
                        },
                    }),
                ]),
                store: new Aui.Store.Remote({
                    url: me.getProcessUrl('drafts'),
                    fields: [
                        { name: 'created_at', type: 'int' },
                        { name: 'size', type: 'int' },
                    ],
                    primaryKeys: ['draft_id'],
                    limit: 50,
                    remoteSort: true,
                    sorters: { created_at: 'DESC' },
                }),
                columns: [
                    {
                        text: (await me.getText('admin.drafts.draft_id')),
                        dataIndex: 'draft_id',
                        sortable: true,
                        width: 275,
                        textClass: 'monospace small',
                    },
                    {
                        text: (await me.getText('admin.attachments.name')),
                        dataIndex: 'name',
                        sortable: true,
                        width: 180,
                        renderer: (value, record) => {
                            return ('<i class="icon" style="background-image:url(' + record.get('icon') + ')"></i>' + value);
                        },
                    },
                    {
                        text: (await me.getText('admin.attachments.path')),
                        dataIndex: 'path',
                        minWidth: 240,
                        flex: 1,
                        textClass: 'monospace small',
                    },
                    {
                        text: (await me.getText('admin.attachments.size')),
                        dataIndex: 'size',
                        sortable: true,
                        width: 90,
                        textClass: 'numeric small',
                        renderer: (value) => {
                            return Format.size(value);
                        },
                    },
                    {
                        text: (await me.getText('admin.drafts.size')),
                        dataIndex: 'realsize',
                        width: 90,
                        textClass: 'numeric small',
                        renderer: (value) => {
                            return Format.size(value);
                        },
                    },
                    {
                        text: (await me.getText('admin.attachments.created_at')),
                        dataIndex: 'created_at',
                        sortable: true,
                        width: 150,
                        renderer: (value) => {
                            return Format.date('Y.m.d(D) H:i', value);
                        },
                    },
                    {
                        text: (await me.getText('admin.drafts.expired_at')),
                        dataIndex: 'expired_at',
                        sortable: true,
                        width: 150,
                        renderer: (value) => {
                            return Format.date('Y.m.d(D) H:i', value);
                        },
                    },
                ],
                listeners: {
                    openItem: (record) => {
                        // @todo 파일 다운로드
                        console.log(record);
                    },
                    openMenu: (menu, record) => {
                        menu.setTitle(record.get('name'));
                        menu.add({
                            text: me.printText('admin.drafts.delete'),
                            iconClass: 'mi mi-trash',
                            handler: async () => {
                                me.drafts.delete();
                                return true;
                            },
                        });
                    },
                    openMenus: (menu, selections) => {
                        menu.setTitle(Aui.printText('texts.selected_item', {
                            count: selections.length.toString(),
                        }));
                        menu.add({
                            text: me.printText('admin.drafts.delete'),
                            iconClass: 'mi mi-trash',
                            handler: async () => {
                                me.drafts.delete();
                                return true;
                            },
                        });
                        // @todo 일괄 다운로드
                    },
                },
            }),
            new Aui.Grid.Panel({
                id: 'trashes',
                iconClass: 'xi xi-trash',
                title: (await me.getText('admin.trashes.title')),
                selection: { selectable: true, display: 'check' },
                autoLoad: false,
                border: false,
                layout: 'fit',
                topbar: [
                    new Aui.Form.Field.Search({
                        width: 200,
                        emptyText: (await me.getText('keyword')),
                        handler: async () => {
                            //
                        },
                    }),
                    '-',
                    new Aui.Button({
                        iconClass: 'mi mi-search',
                        text: (await me.getText('admin.trashes.search')),
                        handler: () => {
                            me.trashes.search();
                        },
                    }),
                    '->',
                    new Aui.Button({
                        iconClass: 'mi mi-trash',
                        text: (await me.getText('admin.trashes.delete_all')),
                        handler: () => {
                            me.trashes.deleteAll();
                        },
                    }),
                ],
                bottombar: new Aui.Grid.Pagination([
                    new Aui.Button({
                        iconClass: 'mi mi-refresh',
                        handler: (button) => {
                            const grid = button.getParent().getParent();
                            grid.getStore().reload();
                        },
                    }),
                ]),
                store: new Aui.Store.Remote({
                    url: me.getProcessUrl('trashes'),
                    fields: [
                        { name: 'created_at', type: 'int' },
                        { name: 'size', type: 'int' },
                    ],
                    primaryKeys: ['path'],
                    limit: 50,
                    remoteSort: true,
                    sorters: { created_at: 'DESC' },
                }),
                columns: [
                    {
                        text: (await me.getText('admin.trashes.path')),
                        dataIndex: 'path',
                        sortable: true,
                        minWidth: 300,
                        flex: 1,
                        textClass: 'monospace small',
                    },
                    {
                        text: (await me.getText('admin.attachments.size')),
                        dataIndex: 'size',
                        sortable: true,
                        width: 90,
                        textClass: 'numeric small',
                        renderer: (value) => {
                            return Format.size(value);
                        },
                    },
                    {
                        text: (await me.getText('admin.trashes.created_at')),
                        dataIndex: 'created_at',
                        sortable: true,
                        width: 150,
                        renderer: (value) => {
                            return Format.date('Y.m.d(D) H:i', value);
                        },
                    },
                ],
                listeners: {
                    openItem: (record) => {
                        // @todo 파일 다운로드
                        console.log(record);
                    },
                    openMenu: (menu, record) => {
                        menu.setTitle(record.get('path'));
                        menu.add({
                            text: me.printText('admin.trashes.delete'),
                            iconClass: 'mi mi-trash',
                            handler: async () => {
                                me.trashes.delete();
                                return true;
                            },
                        });
                    },
                    openMenus: (menu, selections) => {
                        menu.setTitle(Aui.printText('texts.selected_item', {
                            count: selections.length.toString(),
                        }));
                        menu.add({
                            text: me.printText('admin.trashes.delete'),
                            iconClass: 'mi mi-trash',
                            handler: async () => {
                                me.trashes.delete();
                                return true;
                            },
                        });
                        // @todo 일괄 다운로드
                    },
                },
            }),
        ],
        listeners: {
            render: (tab) => {
                const panel = Admin.getContextSubUrl(0);
                if (panel !== null) {
                    tab.active(panel);
                }
            },
            active: (panel) => {
                Aui.getComponent('attachments-context').properties.setUrl();
                if (panel.getStore().isLoaded() == false) {
                    panel.getStore().load();
                }
            },
        },
        setUrl: () => {
            const context = Aui.getComponent('attachments-context');
            if (Admin.getContextSubUrl(0) !== context.getActiveTab().getId()) {
                Admin.setContextSubUrl('/' + context.getActiveTab().getId());
            }
        },
    });
});
