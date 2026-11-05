import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\WorkspaceTimelineController::index
* @see app/Http/Controllers/WorkspaceTimelineController.php:14
* @route '/timeline'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/timeline',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WorkspaceTimelineController::index
* @see app/Http/Controllers/WorkspaceTimelineController.php:14
* @route '/timeline'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WorkspaceTimelineController::index
* @see app/Http/Controllers/WorkspaceTimelineController.php:14
* @route '/timeline'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkspaceTimelineController::index
* @see app/Http/Controllers/WorkspaceTimelineController.php:14
* @route '/timeline'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const timeline = {
    index: Object.assign(index, index),
}

export default timeline