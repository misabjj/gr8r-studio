import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\SystemController::brokenLink
* @see app/Http/Controllers/SystemController.php:20
* @route '/help/broken-link'
*/
export const brokenLink = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: brokenLink.url(options),
    method: 'get',
})

brokenLink.definition = {
    methods: ["get","head"],
    url: '/help/broken-link',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SystemController::brokenLink
* @see app/Http/Controllers/SystemController.php:20
* @route '/help/broken-link'
*/
brokenLink.url = (options?: RouteQueryOptions) => {
    return brokenLink.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SystemController::brokenLink
* @see app/Http/Controllers/SystemController.php:20
* @route '/help/broken-link'
*/
brokenLink.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: brokenLink.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SystemController::brokenLink
* @see app/Http/Controllers/SystemController.php:20
* @route '/help/broken-link'
*/
brokenLink.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: brokenLink.url(options),
    method: 'head',
})

const help = {
    brokenLink: Object.assign(brokenLink, brokenLink),
}

export default help