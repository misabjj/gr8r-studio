import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\SettingsController::index
* @see app/Http/Controllers/SettingsController.php:23
* @route '/settings'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/settings',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::index
* @see app/Http/Controllers/SettingsController.php:23
* @route '/settings'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::index
* @see app/Http/Controllers/SettingsController.php:23
* @route '/settings'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::index
* @see app/Http/Controllers/SettingsController.php:23
* @route '/settings'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/workspace'
*/
const showeb16eb8cfb70c510885edfded2a3b3a1 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showeb16eb8cfb70c510885edfded2a3b3a1.url(options),
    method: 'get',
})

showeb16eb8cfb70c510885edfded2a3b3a1.definition = {
    methods: ["get","head"],
    url: '/settings/workspace',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/workspace'
*/
showeb16eb8cfb70c510885edfded2a3b3a1.url = (options?: RouteQueryOptions) => {
    return showeb16eb8cfb70c510885edfded2a3b3a1.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/workspace'
*/
showeb16eb8cfb70c510885edfded2a3b3a1.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showeb16eb8cfb70c510885edfded2a3b3a1.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/workspace'
*/
showeb16eb8cfb70c510885edfded2a3b3a1.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showeb16eb8cfb70c510885edfded2a3b3a1.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/appearance'
*/
const showe19ee86e9cf603ce1a59a1ec5d21dec5 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showe19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'get',
})

showe19ee86e9cf603ce1a59a1ec5d21dec5.definition = {
    methods: ["get","head"],
    url: '/settings/appearance',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/appearance'
*/
showe19ee86e9cf603ce1a59a1ec5d21dec5.url = (options?: RouteQueryOptions) => {
    return showe19ee86e9cf603ce1a59a1ec5d21dec5.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/appearance'
*/
showe19ee86e9cf603ce1a59a1ec5d21dec5.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showe19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/appearance'
*/
showe19ee86e9cf603ce1a59a1ec5d21dec5.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showe19ee86e9cf603ce1a59a1ec5d21dec5.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/language'
*/
const show47db9583d6c51d67873ea08d7fe90ccb = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show47db9583d6c51d67873ea08d7fe90ccb.url(options),
    method: 'get',
})

show47db9583d6c51d67873ea08d7fe90ccb.definition = {
    methods: ["get","head"],
    url: '/settings/language',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/language'
*/
show47db9583d6c51d67873ea08d7fe90ccb.url = (options?: RouteQueryOptions) => {
    return show47db9583d6c51d67873ea08d7fe90ccb.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/language'
*/
show47db9583d6c51d67873ea08d7fe90ccb.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show47db9583d6c51d67873ea08d7fe90ccb.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/language'
*/
show47db9583d6c51d67873ea08d7fe90ccb.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show47db9583d6c51d67873ea08d7fe90ccb.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/datetime'
*/
const showa80b106dbd6ef2f729f30c3165ef95ca = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showa80b106dbd6ef2f729f30c3165ef95ca.url(options),
    method: 'get',
})

showa80b106dbd6ef2f729f30c3165ef95ca.definition = {
    methods: ["get","head"],
    url: '/settings/datetime',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/datetime'
*/
showa80b106dbd6ef2f729f30c3165ef95ca.url = (options?: RouteQueryOptions) => {
    return showa80b106dbd6ef2f729f30c3165ef95ca.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/datetime'
*/
showa80b106dbd6ef2f729f30c3165ef95ca.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showa80b106dbd6ef2f729f30c3165ef95ca.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/datetime'
*/
showa80b106dbd6ef2f729f30c3165ef95ca.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showa80b106dbd6ef2f729f30c3165ef95ca.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/members'
*/
const show43a7781e7d2c35bfb9b5851b2328689b = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show43a7781e7d2c35bfb9b5851b2328689b.url(options),
    method: 'get',
})

show43a7781e7d2c35bfb9b5851b2328689b.definition = {
    methods: ["get","head"],
    url: '/settings/members',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/members'
*/
show43a7781e7d2c35bfb9b5851b2328689b.url = (options?: RouteQueryOptions) => {
    return show43a7781e7d2c35bfb9b5851b2328689b.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/members'
*/
show43a7781e7d2c35bfb9b5851b2328689b.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show43a7781e7d2c35bfb9b5851b2328689b.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/members'
*/
show43a7781e7d2c35bfb9b5851b2328689b.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show43a7781e7d2c35bfb9b5851b2328689b.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/teams'
*/
const show73bc24a6960b67f9ca4ae6979b030f8f = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show73bc24a6960b67f9ca4ae6979b030f8f.url(options),
    method: 'get',
})

show73bc24a6960b67f9ca4ae6979b030f8f.definition = {
    methods: ["get","head"],
    url: '/settings/teams',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/teams'
*/
show73bc24a6960b67f9ca4ae6979b030f8f.url = (options?: RouteQueryOptions) => {
    return show73bc24a6960b67f9ca4ae6979b030f8f.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/teams'
*/
show73bc24a6960b67f9ca4ae6979b030f8f.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show73bc24a6960b67f9ca4ae6979b030f8f.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/teams'
*/
show73bc24a6960b67f9ca4ae6979b030f8f.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show73bc24a6960b67f9ca4ae6979b030f8f.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/projects'
*/
const showc4ba868b722e1afc6bf900084e8af428 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showc4ba868b722e1afc6bf900084e8af428.url(options),
    method: 'get',
})

showc4ba868b722e1afc6bf900084e8af428.definition = {
    methods: ["get","head"],
    url: '/settings/projects',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/projects'
*/
showc4ba868b722e1afc6bf900084e8af428.url = (options?: RouteQueryOptions) => {
    return showc4ba868b722e1afc6bf900084e8af428.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/projects'
*/
showc4ba868b722e1afc6bf900084e8af428.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showc4ba868b722e1afc6bf900084e8af428.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/projects'
*/
showc4ba868b722e1afc6bf900084e8af428.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showc4ba868b722e1afc6bf900084e8af428.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/permissions'
*/
const show6fc8e0c15e072f1de1e11e443960d4ae = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show6fc8e0c15e072f1de1e11e443960d4ae.url(options),
    method: 'get',
})

show6fc8e0c15e072f1de1e11e443960d4ae.definition = {
    methods: ["get","head"],
    url: '/settings/permissions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/permissions'
*/
show6fc8e0c15e072f1de1e11e443960d4ae.url = (options?: RouteQueryOptions) => {
    return show6fc8e0c15e072f1de1e11e443960d4ae.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/permissions'
*/
show6fc8e0c15e072f1de1e11e443960d4ae.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show6fc8e0c15e072f1de1e11e443960d4ae.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/permissions'
*/
show6fc8e0c15e072f1de1e11e443960d4ae.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show6fc8e0c15e072f1de1e11e443960d4ae.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-email'
*/
const show05e76974448126fd90be5c2155198a43 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show05e76974448126fd90be5c2155198a43.url(options),
    method: 'get',
})

show05e76974448126fd90be5c2155198a43.definition = {
    methods: ["get","head"],
    url: '/settings/notif-email',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-email'
*/
show05e76974448126fd90be5c2155198a43.url = (options?: RouteQueryOptions) => {
    return show05e76974448126fd90be5c2155198a43.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-email'
*/
show05e76974448126fd90be5c2155198a43.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show05e76974448126fd90be5c2155198a43.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-email'
*/
show05e76974448126fd90be5c2155198a43.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show05e76974448126fd90be5c2155198a43.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-push'
*/
const showebca02a13083931a231de25e9dfc9fdc = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showebca02a13083931a231de25e9dfc9fdc.url(options),
    method: 'get',
})

showebca02a13083931a231de25e9dfc9fdc.definition = {
    methods: ["get","head"],
    url: '/settings/notif-push',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-push'
*/
showebca02a13083931a231de25e9dfc9fdc.url = (options?: RouteQueryOptions) => {
    return showebca02a13083931a231de25e9dfc9fdc.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-push'
*/
showebca02a13083931a231de25e9dfc9fdc.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showebca02a13083931a231de25e9dfc9fdc.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-push'
*/
showebca02a13083931a231de25e9dfc9fdc.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showebca02a13083931a231de25e9dfc9fdc.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-mentions'
*/
const show5383a3c2223d76cf0c1dbc14f531500c = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show5383a3c2223d76cf0c1dbc14f531500c.url(options),
    method: 'get',
})

show5383a3c2223d76cf0c1dbc14f531500c.definition = {
    methods: ["get","head"],
    url: '/settings/notif-mentions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-mentions'
*/
show5383a3c2223d76cf0c1dbc14f531500c.url = (options?: RouteQueryOptions) => {
    return show5383a3c2223d76cf0c1dbc14f531500c.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-mentions'
*/
show5383a3c2223d76cf0c1dbc14f531500c.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show5383a3c2223d76cf0c1dbc14f531500c.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-mentions'
*/
show5383a3c2223d76cf0c1dbc14f531500c.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show5383a3c2223d76cf0c1dbc14f531500c.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-assign'
*/
const showa522e4b145c948ad942ba497dea5ae22 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showa522e4b145c948ad942ba497dea5ae22.url(options),
    method: 'get',
})

showa522e4b145c948ad942ba497dea5ae22.definition = {
    methods: ["get","head"],
    url: '/settings/notif-assign',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-assign'
*/
showa522e4b145c948ad942ba497dea5ae22.url = (options?: RouteQueryOptions) => {
    return showa522e4b145c948ad942ba497dea5ae22.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-assign'
*/
showa522e4b145c948ad942ba497dea5ae22.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showa522e4b145c948ad942ba497dea5ae22.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/notif-assign'
*/
showa522e4b145c948ad942ba497dea5ae22.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showa522e4b145c948ad942ba497dea5ae22.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/profile'
*/
const showfc6874003af373efc88e5e18eecd9c17 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showfc6874003af373efc88e5e18eecd9c17.url(options),
    method: 'get',
})

showfc6874003af373efc88e5e18eecd9c17.definition = {
    methods: ["get","head"],
    url: '/settings/profile',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/profile'
*/
showfc6874003af373efc88e5e18eecd9c17.url = (options?: RouteQueryOptions) => {
    return showfc6874003af373efc88e5e18eecd9c17.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/profile'
*/
showfc6874003af373efc88e5e18eecd9c17.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showfc6874003af373efc88e5e18eecd9c17.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/profile'
*/
showfc6874003af373efc88e5e18eecd9c17.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showfc6874003af373efc88e5e18eecd9c17.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/preferences'
*/
const showf21561fd4f52c53a4e9ca4a0083aec08 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showf21561fd4f52c53a4e9ca4a0083aec08.url(options),
    method: 'get',
})

showf21561fd4f52c53a4e9ca4a0083aec08.definition = {
    methods: ["get","head"],
    url: '/settings/preferences',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/preferences'
*/
showf21561fd4f52c53a4e9ca4a0083aec08.url = (options?: RouteQueryOptions) => {
    return showf21561fd4f52c53a4e9ca4a0083aec08.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/preferences'
*/
showf21561fd4f52c53a4e9ca4a0083aec08.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: showf21561fd4f52c53a4e9ca4a0083aec08.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/preferences'
*/
showf21561fd4f52c53a4e9ca4a0083aec08.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: showf21561fd4f52c53a4e9ca4a0083aec08.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/shortcuts'
*/
const show99712b71f3edf4ef9ce21d119c7935f1 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show99712b71f3edf4ef9ce21d119c7935f1.url(options),
    method: 'get',
})

show99712b71f3edf4ef9ce21d119c7935f1.definition = {
    methods: ["get","head"],
    url: '/settings/shortcuts',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/shortcuts'
*/
show99712b71f3edf4ef9ce21d119c7935f1.url = (options?: RouteQueryOptions) => {
    return show99712b71f3edf4ef9ce21d119c7935f1.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/shortcuts'
*/
show99712b71f3edf4ef9ce21d119c7935f1.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show99712b71f3edf4ef9ce21d119c7935f1.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/shortcuts'
*/
show99712b71f3edf4ef9ce21d119c7935f1.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show99712b71f3edf4ef9ce21d119c7935f1.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/password'
*/
const show3d83238d90f1e3969ebe570175cfec66 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show3d83238d90f1e3969ebe570175cfec66.url(options),
    method: 'get',
})

show3d83238d90f1e3969ebe570175cfec66.definition = {
    methods: ["get","head"],
    url: '/settings/password',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/password'
*/
show3d83238d90f1e3969ebe570175cfec66.url = (options?: RouteQueryOptions) => {
    return show3d83238d90f1e3969ebe570175cfec66.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/password'
*/
show3d83238d90f1e3969ebe570175cfec66.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show3d83238d90f1e3969ebe570175cfec66.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/password'
*/
show3d83238d90f1e3969ebe570175cfec66.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show3d83238d90f1e3969ebe570175cfec66.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/sessions'
*/
const show0a5a655dd88676cb878024ee631d6cef = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show0a5a655dd88676cb878024ee631d6cef.url(options),
    method: 'get',
})

show0a5a655dd88676cb878024ee631d6cef.definition = {
    methods: ["get","head"],
    url: '/settings/sessions',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/sessions'
*/
show0a5a655dd88676cb878024ee631d6cef.url = (options?: RouteQueryOptions) => {
    return show0a5a655dd88676cb878024ee631d6cef.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/sessions'
*/
show0a5a655dd88676cb878024ee631d6cef.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show0a5a655dd88676cb878024ee631d6cef.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/sessions'
*/
show0a5a655dd88676cb878024ee631d6cef.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show0a5a655dd88676cb878024ee631d6cef.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/2fa'
*/
const show83d951d773dbc2efb1755d2c993e61cd = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show83d951d773dbc2efb1755d2c993e61cd.url(options),
    method: 'get',
})

show83d951d773dbc2efb1755d2c993e61cd.definition = {
    methods: ["get","head"],
    url: '/settings/2fa',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/2fa'
*/
show83d951d773dbc2efb1755d2c993e61cd.url = (options?: RouteQueryOptions) => {
    return show83d951d773dbc2efb1755d2c993e61cd.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/2fa'
*/
show83d951d773dbc2efb1755d2c993e61cd.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show83d951d773dbc2efb1755d2c993e61cd.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/2fa'
*/
show83d951d773dbc2efb1755d2c993e61cd.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show83d951d773dbc2efb1755d2c993e61cd.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/plan'
*/
const show593b69aec00bd1063fc6c1870c633735 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show593b69aec00bd1063fc6c1870c633735.url(options),
    method: 'get',
})

show593b69aec00bd1063fc6c1870c633735.definition = {
    methods: ["get","head"],
    url: '/settings/plan',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/plan'
*/
show593b69aec00bd1063fc6c1870c633735.url = (options?: RouteQueryOptions) => {
    return show593b69aec00bd1063fc6c1870c633735.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/plan'
*/
show593b69aec00bd1063fc6c1870c633735.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show593b69aec00bd1063fc6c1870c633735.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/plan'
*/
show593b69aec00bd1063fc6c1870c633735.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show593b69aec00bd1063fc6c1870c633735.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/payment'
*/
const show7b93a502fcc22da7e0b29c1db1756813 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show7b93a502fcc22da7e0b29c1db1756813.url(options),
    method: 'get',
})

show7b93a502fcc22da7e0b29c1db1756813.definition = {
    methods: ["get","head"],
    url: '/settings/payment',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/payment'
*/
show7b93a502fcc22da7e0b29c1db1756813.url = (options?: RouteQueryOptions) => {
    return show7b93a502fcc22da7e0b29c1db1756813.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/payment'
*/
show7b93a502fcc22da7e0b29c1db1756813.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show7b93a502fcc22da7e0b29c1db1756813.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/payment'
*/
show7b93a502fcc22da7e0b29c1db1756813.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show7b93a502fcc22da7e0b29c1db1756813.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/invoices'
*/
const show8d802a96daeaa1a13968f1e485697153 = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show8d802a96daeaa1a13968f1e485697153.url(options),
    method: 'get',
})

show8d802a96daeaa1a13968f1e485697153.definition = {
    methods: ["get","head"],
    url: '/settings/invoices',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/invoices'
*/
show8d802a96daeaa1a13968f1e485697153.url = (options?: RouteQueryOptions) => {
    return show8d802a96daeaa1a13968f1e485697153.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/invoices'
*/
show8d802a96daeaa1a13968f1e485697153.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show8d802a96daeaa1a13968f1e485697153.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SettingsController::show
* @see app/Http/Controllers/SettingsController.php:33
* @route '/settings/invoices'
*/
show8d802a96daeaa1a13968f1e485697153.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show8d802a96daeaa1a13968f1e485697153.url(options),
    method: 'head',
})

/**
* Multiple routes resolve to \App\Http\Controllers\SettingsController::show, so this export is a
* dictionary keyed by URI rather than a callable, with the verbs prefixed where two routes share a URI.
* Call a specific route with `show['<key>'](...)`, or import the route by name from your
* generated `routes/` directory.
*/
export const show = {
    '/settings/workspace': showeb16eb8cfb70c510885edfded2a3b3a1,
    '/settings/appearance': showe19ee86e9cf603ce1a59a1ec5d21dec5,
    '/settings/language': show47db9583d6c51d67873ea08d7fe90ccb,
    '/settings/datetime': showa80b106dbd6ef2f729f30c3165ef95ca,
    '/settings/members': show43a7781e7d2c35bfb9b5851b2328689b,
    '/settings/teams': show73bc24a6960b67f9ca4ae6979b030f8f,
    '/settings/projects': showc4ba868b722e1afc6bf900084e8af428,
    '/settings/permissions': show6fc8e0c15e072f1de1e11e443960d4ae,
    '/settings/notif-email': show05e76974448126fd90be5c2155198a43,
    '/settings/notif-push': showebca02a13083931a231de25e9dfc9fdc,
    '/settings/notif-mentions': show5383a3c2223d76cf0c1dbc14f531500c,
    '/settings/notif-assign': showa522e4b145c948ad942ba497dea5ae22,
    '/settings/profile': showfc6874003af373efc88e5e18eecd9c17,
    '/settings/preferences': showf21561fd4f52c53a4e9ca4a0083aec08,
    '/settings/shortcuts': show99712b71f3edf4ef9ce21d119c7935f1,
    '/settings/password': show3d83238d90f1e3969ebe570175cfec66,
    '/settings/sessions': show0a5a655dd88676cb878024ee631d6cef,
    '/settings/2fa': show83d951d773dbc2efb1755d2c993e61cd,
    '/settings/plan': show593b69aec00bd1063fc6c1870c633735,
    '/settings/payment': show7b93a502fcc22da7e0b29c1db1756813,
    '/settings/invoices': show8d802a96daeaa1a13968f1e485697153,
}

const SettingsController = { index, show }

export default SettingsController