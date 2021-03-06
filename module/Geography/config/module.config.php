<?php

return array(

    // Controllers in this module
    'controller' => array(
        'classes' => array(
            'geography/region' => 'Geography\Controller\RegionController',
        ),
    ),

    // Routes for this module
    'router' => array(
        'routes' => array(
            'region' => array(
                'type'    => 'segment',
                'options' => array(
                    'route'    => '/region[/:action][/:id]',
                    'constraints' => array(
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'id'     => '[0-9]+',
                    ),
                    'defaults' => array(
                        'controller' => 'geography/region',
                        'action'     => 'index',
                    ),
                ),
            ),
        ),
    ),    

    // View setup for this module
    'view_manager' => array(
        'template_path_stack' => array(
            'region' => __DIR__ . '/../view',
        ),
    ),
);
