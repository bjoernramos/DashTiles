<?php

return [
    'brand' => 'toolpages',

    // Navbar
    'nav' => [
        'start'    => 'Home',
        'dashboard'=> 'Dashboard',
        'admin'    => 'Admin',
        'users'    => 'Users',
        'groups'   => 'Groups',
        'login'    => 'Login',
        'logout'   => 'Logout',
        'language' => 'Language',
    ],

    // Generic actions
    'actions' => [
        'back'    => 'Back',
        'new'     => 'New',
        'save'    => 'Save',
        'close'   => 'Close',
        'delete'  => 'Delete',
        'edit'    => 'Edit',
        'create'  => 'Create',
        'update'  => 'Update',
        'open'    => 'Open',
        'members' => 'Members',
      ],

    // Greetings
    'hello' => 'Hello',
    
    // Pages / Titles
    'pages' => [
        'home' => [
            'title' => 'Home',
            'welcome_logged_out' => 'After logging in your tiles will show up here on the home page.',
            'no_tiles' => 'No tiles yet. Create some in the Dashboard.',
            'collapse_toggle' => 'Toggle category',
            'collapse_label' => 'Toggle',
        ],
        'dashboard' => [
            'title' => 'My Dashboard',
            'back' => 'Back',
            'logout' => 'Logout',
            'columns' => 'Columns',
            'save_layout' => 'Save layout',
            'add_tile' => 'Add tile',
            'add' => 'New',
            'tabs' => [ 'link' => 'Link', 'file' => 'File', 'iframe' => 'iFrame' ],
            'labels' => [
                'title' => 'Title',
                'url' => 'URL',
                'icon' => 'Icon',
                'text' => 'Text',
                'category' => 'Category',
                'position' => 'Position',
                'file' => 'File',
                'new_file' => 'New file (optional)',
                'users' => 'Only for specific users',
                'groups' => 'Only for specific groups',
                'allowed_users' => 'Users allowed to see this tile',
                'allowed_groups' => 'Groups allowed to see this tile',
                'global' => 'Global (show to all users)'
            ],
            'edit_tile' => 'Edit tile',
            'delete_tile_confirm' => 'Delete tile?',
            'deleted' => 'Tile deleted',
            'updated' => 'Tile updated',
            'created' => 'Tile created',
            'global_badge' => 'Global',
        ],
        'groups' => [
            'index_title' => 'Groups',
            'create_title' => 'Create group',
            'name' => 'Name',
            'members' => 'Members',
            'actions' => 'Actions',
            'manage_members' => 'Manage members',
            'users_in_group' => 'Users in this group',
            'delete_confirm' => 'Really delete this group? Related memberships and tile assignments will be removed.',
            'none' => 'No groups available.',
            'created' => 'Group created',
            'deleted' => 'Group deleted',
            'not_found' => 'Group not found',
            'updated_members' => 'Members updated',
            'group_name' => 'Group name',
        ],
        'users' => [
            'index_title' => 'Users',
            'create_title' => 'Create user',
            'id' => 'ID',
            'username' => 'Username',
            'display_name' => 'Display name',
            'email' => 'Email',
            'source' => 'Source',
            'role' => 'Role',
            'active' => 'Active',
            'yes' => 'yes',
            'no' => 'no',
            'edit_user' => 'Edit user',
            'deactivate' => 'Deactivate',
            'activate' => 'Activate',
            'delete_confirm' => 'Delete this user including personal tiles?',
        ],
        'auth' => [
            'login' => 'Login',
            'tabs' => [ 'standard' => 'Standard', 'ldap' => 'LDAP' ],
            'local_login' => 'Local login',
            'ldap_login' => 'LDAP login',
            'password' => 'Password',
            'login_btn' => 'Login',
            'login_with_ldap' => 'Login with LDAP',
        ]
    ],
];
