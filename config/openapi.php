<?php

return [
    'openapi' => '3.0.3',
    'info' => [
        'title' => env('APP_NAME', 'Laravel').' API',
        'version' => '1.0.0',
    ],
    'components' => [
        'securitySchemes' => [
            'bearerAuth' => [
                'type' => 'http',
                'scheme' => 'bearer',
            ],
        ],
        'schemas' => [
            'ApiUser' => [
                'type' => 'object',
                'properties' => [
                    'id' => ['type' => 'integer'],
                    'name' => ['type' => 'string'],
                    'email' => ['type' => 'string', 'format' => 'email'],
                    'created_at' => ['type' => 'string', 'format' => 'date-time'],
                    'updated_at' => ['type' => 'string', 'format' => 'date-time'],
                ],
            ],
        ],
    ],
    'paths' => [
        '/register' => [
            'post' => [
                'summary' => 'Signup API user',
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['name', 'email', 'password', 'password_confirmation'],
                                'properties' => [
                                    'name' => ['type' => 'string', 'example' => 'API User'],
                                    'email' => ['type' => 'string', 'format' => 'email', 'example' => 'api@example.com'],
                                    'password' => ['type' => 'string', 'example' => 'password'],
                                    'password_confirmation' => ['type' => 'string', 'example' => 'password'],
                                    'device_name' => ['type' => 'string', 'example' => 'mobile'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '201' => ['description' => 'Account created with bearer token'],
                    '422' => ['description' => 'Validation error'],
                ],
            ],
        ],
        '/shops/register' => [
            'post' => [
                'summary' => 'Register shop with owner user',
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['shop', 'owner'],
                                'properties' => [
                                    'shop' => [
                                        'type' => 'object',
                                        'required' => ['name'],
                                        'properties' => [
                                            'name' => ['type' => 'string', 'example' => 'City Mart'],
                                            'slug' => ['type' => 'string', 'example' => 'city-mart'],
                                            'email' => ['type' => 'string', 'format' => 'email', 'example' => 'shop@example.com'],
                                            'phone' => ['type' => 'string', 'example' => '03001234567'],
                                            'address' => ['type' => 'string', 'example' => 'Main Market'],
                                            'city' => ['type' => 'string', 'example' => 'Lahore'],
                                        ],
                                    ],
                                    'owner' => [
                                        'type' => 'object',
                                        'required' => ['name', 'email', 'password', 'password_confirmation'],
                                        'properties' => [
                                            'name' => ['type' => 'string', 'example' => 'Shop Owner'],
                                            'email' => ['type' => 'string', 'format' => 'email', 'example' => 'owner@example.com'],
                                            'password' => ['type' => 'string', 'example' => 'password'],
                                            'password_confirmation' => ['type' => 'string', 'example' => 'password'],
                                        ],
                                    ],
                                    'device_name' => ['type' => 'string', 'example' => 'mobile'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '201' => ['description' => 'Shop, owner user, and owner relation created'],
                    '422' => ['description' => 'Validation error'],
                ],
            ],
        ],        '/login' => [
            'post' => [
                'summary' => 'Login API user',
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['email', 'password'],
                                'properties' => [
                                    'email' => ['type' => 'string', 'format' => 'email', 'example' => 'api@example.com'],
                                    'password' => ['type' => 'string', 'example' => 'password'],
                                    'device_name' => ['type' => 'string', 'example' => 'mobile'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => ['description' => 'Bearer token created'],
                    '422' => ['description' => 'Invalid credentials'],
                ],
            ],
        ],
        '/forgot-password' => [
            'post' => [
                'summary' => 'Generate user password reset OTP',
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['email'],
                                'properties' => [
                                    'email' => ['type' => 'string', 'format' => 'email', 'example' => 'api@example.com'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => ['description' => 'Reset OTP generated'],
                    '422' => ['description' => 'Validation error'],
                ],
            ],
        ],
        '/reset-password' => [
            'post' => [
                'summary' => 'Reset user password with OTP',
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['email', 'otp', 'password', 'password_confirmation'],
                                'properties' => [
                                    'email' => ['type' => 'string', 'format' => 'email', 'example' => 'api@example.com'],
                                    'otp' => ['type' => 'string', 'example' => '123456'],
                                    'password' => ['type' => 'string', 'example' => 'new-password'],
                                    'password_confirmation' => ['type' => 'string', 'example' => 'new-password'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => ['description' => 'Password reset successfully'],
                    '422' => ['description' => 'Invalid or expired OTP'],
                ],
            ],
        ],
        '/me' => [
            'get' => [
                'summary' => 'Current API user profile',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'Current API user'],
                    '401' => ['description' => 'Unauthenticated'],
                ],
            ],
        ],
        '/logout' => [
            'post' => [
                'summary' => 'Revoke current API token',
                'security' => [['bearerAuth' => []]],
                'responses' => [
                    '200' => ['description' => 'Logged out'],
                    '401' => ['description' => 'Unauthenticated'],
                ],
            ],
        ],
        '/change-password' => [
            'post' => [
                'summary' => 'Change authenticated user password',
                'security' => [['bearerAuth' => []]],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['current_password', 'password', 'password_confirmation'],
                                'properties' => [
                                    'current_password' => ['type' => 'string', 'example' => 'password'],
                                    'password' => ['type' => 'string', 'example' => 'new-password'],
                                    'password_confirmation' => ['type' => 'string', 'example' => 'new-password'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => ['description' => 'Password changed successfully'],
                    '422' => ['description' => 'Current password is incorrect'],
                ],
            ],
        ],
        '/shops/{shop}/categories' => [
            'get' => [
                'summary' => 'List shop categories',
                'security' => [['bearerAuth' => []]],
                'parameters' => [['name' => 'shop', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                'responses' => ['200' => ['description' => 'Paginated categories']],
            ],
            'post' => [
                'summary' => 'Create shop category',
                'security' => [['bearerAuth' => []]],
                'parameters' => [['name' => 'shop', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['name'],
                                'properties' => [
                                    'name' => ['type' => 'string', 'example' => 'Beverages'],
                                    'slug' => ['type' => 'string', 'example' => 'beverages'],
                                    'status' => ['type' => 'string', 'example' => 'active'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => ['201' => ['description' => 'Category created'], '422' => ['description' => 'Validation error']],
            ],
        ],
        '/shops/{shop}/products' => [
            'get' => [
                'summary' => 'List shop products',
                'security' => [['bearerAuth' => []]],
                'parameters' => [['name' => 'shop', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                'responses' => ['200' => ['description' => 'Paginated products']],
            ],
            'post' => [
                'summary' => 'Create product with opening stock log',
                'security' => [['bearerAuth' => []]],
                'parameters' => [['name' => 'shop', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['name', 'purchase_price', 'sale_price', 'stock_quantity'],
                                'properties' => [
                                    'category_id' => ['type' => 'integer', 'example' => 1],
                                    'name' => ['type' => 'string', 'example' => 'Milk Pack'],
                                    'sku' => ['type' => 'string', 'example' => 'MILK-001'],
                                    'purchase_price' => ['type' => 'number', 'example' => 120],
                                    'sale_price' => ['type' => 'number', 'example' => 150],
                                    'stock_quantity' => ['type' => 'integer', 'example' => 10],
                                    'note' => ['type' => 'string', 'example' => 'Opening stock'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => ['201' => ['description' => 'Product created'], '422' => ['description' => 'Validation error']],
            ],
        ],
        '/shops/{shop}/products/report' => [
            'get' => [
                'summary' => 'Shop product detail report',
                'security' => [['bearerAuth' => []]],
                'parameters' => [['name' => 'shop', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                'responses' => ['200' => ['description' => 'Product stock and value summary']],
            ],
        ],
        '/shops/{shop}/products/{product}' => [
            'get' => [
                'summary' => 'Show product details with stock logs',
                'security' => [['bearerAuth' => []]],
                'parameters' => [
                    ['name' => 'shop', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ['name' => 'product', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                ],
                'responses' => ['200' => ['description' => 'Product details and paginated stock logs']],
            ],
        ],
        '/shops/{shop}/products/{product}/stock' => [
            'post' => [
                'summary' => 'Update stock and prices with stock log',
                'security' => [['bearerAuth' => []]],
                'parameters' => [
                    ['name' => 'shop', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                    ['name' => 'product', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']],
                ],
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['purchase_price', 'sale_price', 'stock_quantity'],
                                'properties' => [
                                    'purchase_price' => ['type' => 'number', 'example' => 125],
                                    'sale_price' => ['type' => 'number', 'example' => 160],
                                    'stock_quantity' => ['type' => 'integer', 'example' => 18],
                                    'note' => ['type' => 'string', 'example' => 'Purchased new stock'],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => ['200' => ['description' => 'Stock updated with latest log'], '422' => ['description' => 'Validation error']],
            ],
        ],        '/users' => [
            'get' => [
                'summary' => 'List API users',
                'security' => [['bearerAuth' => []]],
                'responses' => ['200' => ['description' => 'Paginated API users']],
            ],
            'post' => [
                'summary' => 'Create API user',
                'security' => [['bearerAuth' => []]],
                'responses' => ['201' => ['description' => 'API user created']],
            ],
        ],
        '/users/{user}' => [
            'get' => [
                'summary' => 'Show API user',
                'security' => [['bearerAuth' => []]],
                'parameters' => [['name' => 'user', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                'responses' => ['200' => ['description' => 'API user']],
            ],
            'put' => [
                'summary' => 'Update API user',
                'security' => [['bearerAuth' => []]],
                'parameters' => [['name' => 'user', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                'responses' => ['200' => ['description' => 'API user updated']],
            ],
            'delete' => [
                'summary' => 'Delete API user',
                'security' => [['bearerAuth' => []]],
                'parameters' => [['name' => 'user', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'integer']]],
                'responses' => ['204' => ['description' => 'API user deleted']],
            ],
        ],
    ],
];
