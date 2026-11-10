<?php

return [
    'super_admin_email' => env('SUPER_ADMIN_EMAIL', 'admin@gmail.com'),

    'groups' => [
        'operations' => [
            'label' => 'Opérations terrain',
            'modules' => [
                'clients' => [
                    'label' => 'Clients',
                    'permissions' => [
                        'clients.view' => 'Consulter',
                        'clients.create' => 'Créer',
                        'clients.update' => 'Modifier',
                        'clients.delete' => 'Supprimer',
                        'clients.export' => 'Exporter',
                    ],
                ],
                'agents' => [
                    'label' => 'Agents',
                    'permissions' => [
                        'agents.view' => 'Consulter',
                        'agents.create' => 'Créer',
                        'agents.update' => 'Modifier',
                        'agents.delete' => 'Supprimer',
                        'agents.activate' => 'Activer / désactiver',
                        'agents.export' => 'Exporter',
                        'agents.commissions' => 'Gérer les commissions',
                    ],
                ],
                'carnets' => [
                    'label' => 'Carnets & tontines',
                    'permissions' => [
                        'carnets.view' => 'Consulter',
                        'carnets.create' => 'Créer',
                        'carnets.update' => 'Modifier',
                        'carnets.delete' => 'Supprimer',
                        'carnets.deposit' => 'Enregistrer un dépôt',
                        'carnets.withdraw' => 'Enregistrer un retrait',
                        'carnets.stock' => 'Gérer le stock',
                        'cycles.view' => 'Consulter les cycles',
                        'cycles.manage' => 'Gérer les cycles',
                    ],
                ],
                'credits' => [
                    'label' => 'Crédits & prêts',
                    'permissions' => [
                        'credits.view' => 'Consulter',
                        'credits.create' => 'Créer une demande',
                        'credits.update' => 'Modifier',
                        'credits.approve' => 'Approuver',
                        'credits.disburse' => 'Décaisser',
                        'credits.payments' => 'Enregistrer un remboursement',
                        'credits.settle' => 'Compenser avec l’épargne',
                        'credits.configure' => 'Configurer les produits',
                    ],
                ],
                'synchronization' => [
                    'label' => 'Synchronisations',
                    'permissions' => [
                        'sync.view' => 'Consulter',
                        'sync.validate' => 'Valider / rejeter',
                    ],
                ],
            ],
        ],
        'finance' => [
            'label' => 'Trésorerie & comptabilité',
            'modules' => [
                'treasury' => [
                    'label' => 'Caisse & trésorerie',
                    'permissions' => [
                        'treasury.view' => 'Consulter le dashboard et le journal',
                        'treasury.open' => 'Ouvrir une caisse',
                        'treasury.replenish' => 'Approvisionner une caisse',
                        'treasury.close' => 'Clôturer une caisse',
                    ],
                ],
                'expenses' => [
                    'label' => 'Dépenses',
                    'permissions' => [
                        'expenses.view' => 'Consulter',
                        'expenses.create' => 'Créer',
                        'expenses.update' => 'Modifier',
                        'expenses.delete' => 'Supprimer',
                    ],
                ],
                'revenues' => [
                    'label' => 'Recettes',
                    'permissions' => [
                        'revenues.view' => 'Consulter',
                        'revenues.create' => 'Créer',
                    ],
                ],
                'charges' => [
                    'label' => 'Types & catégories de charges',
                    'permissions' => [
                        'charges.view' => 'Consulter',
                        'charges.manage' => 'Gérer les catégories',
                    ],
                ],
                'commissions' => [
                    'label' => 'Commissions',
                    'permissions' => [
                        'commissions.view' => 'Consulter',
                        'commissions.manage' => 'Valider et gérer',
                    ],
                ],
            ],
        ],
        'human_resources' => [
            'label' => 'Ressources humaines',
            'modules' => [
                'employees' => [
                    'label' => 'Personnel',
                    'permissions' => [
                        'employees.view' => 'Consulter',
                        'employees.create' => 'Créer',
                        'employees.update' => 'Modifier',
                        'employees.delete' => 'Supprimer',
                    ],
                ],
                'payroll' => [
                    'label' => 'Salaires & avances',
                    'permissions' => [
                        'payroll.view' => 'Consulter',
                        'payroll.manage' => 'Gérer les salaires et avances',
                    ],
                ],
            ],
        ],
        'administration' => [
            'label' => 'Administration & sécurité',
            'modules' => [
                'users' => [
                    'label' => 'Utilisateurs',
                    'permissions' => [
                        'users.view' => 'Consulter',
                        'users.create' => 'Créer',
                        'users.update' => 'Modifier',
                        'users.delete' => 'Supprimer',
                    ],
                ],
                'roles' => [
                    'label' => 'Rôles & permissions',
                    'permissions' => [
                        'roles.view' => 'Consulter',
                        'roles.create' => 'Créer',
                        'roles.update' => 'Modifier',
                        'roles.delete' => 'Supprimer',
                    ],
                ],
                'audits' => [
                    'label' => 'Journal d’audit',
                    'permissions' => [
                        'audits.view' => 'Consulter',
                        'audits.export' => 'Exporter',
                    ],
                ],
                'reports' => [
                    'label' => 'Rapports',
                    'permissions' => [
                        'reports.view' => 'Consulter',
                        'reports.export' => 'Exporter',
                    ],
                ],
            ],
        ],
    ],

    'legacy_aliases' => [
        'Gérer Agents' => [
            'agents.view', 'agents.create', 'agents.update', 'agents.delete',
            'agents.activate', 'agents.export', 'agents.commissions',
        ],
        'Gérer Clients' => [
            'clients.view', 'clients.create', 'clients.update', 'clients.delete', 'clients.export',
        ],
        'Gérer Utilisateurs' => [
            'users.view', 'users.create', 'users.update', 'users.delete',
            'roles.view', 'roles.create', 'roles.update', 'roles.delete',
        ],
        'Gérer Collectes' => ['carnets.view', 'carnets.create', 'carnets.deposit', 'carnets.withdraw'],
        'Gérer Commissions' => ['commissions.view', 'commissions.manage', 'agents.commissions'],
        'Gérer Crédits' => [
            'credits.view', 'credits.create', 'credits.update', 'credits.approve',
            'credits.disburse', 'credits.payments', 'credits.settle', 'credits.configure',
        ],
        'Gérer Carnets' => [
            'carnets.view', 'carnets.create', 'carnets.update', 'carnets.delete',
            'carnets.deposit', 'carnets.withdraw', 'carnets.stock',
        ],
        'Valider Synchros' => ['sync.view', 'sync.validate'],
        'Gérer Synchros' => ['sync.view', 'sync.validate'],
        'Voir Rapports' => ['reports.view', 'reports.export'],
        'Gérer Salaires' => ['payroll.view', 'payroll.manage', 'employees.view'],
        'Consulter la trésorerie' => ['treasury.view'],
        'Consulter les audits' => ['audits.view'],
        'Exporter les audits' => ['audits.export'],
        'Modifier données' => [
            'clients.update', 'agents.update', 'carnets.update', 'credits.update',
            'expenses.update', 'employees.update', 'users.update', 'roles.update',
        ],
        'Supprimer données' => [
            'clients.delete', 'agents.delete', 'carnets.delete', 'expenses.delete',
            'employees.delete', 'users.delete', 'roles.delete',
        ],
        'Supprimer Données' => [
            'clients.delete', 'agents.delete', 'carnets.delete', 'expenses.delete',
            'employees.delete', 'users.delete', 'roles.delete',
        ],
        'Activer/Désactiver' => ['agents.activate'],
    ],
];
