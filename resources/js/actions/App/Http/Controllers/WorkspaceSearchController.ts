import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\WorkspaceSearchController::index
* @see app/Http/Controllers/WorkspaceSearchController.php:14
* @route '/search'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/search',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WorkspaceSearchController::index
* @see app/Http/Controllers/WorkspaceSearchController.php:14
* @route '/search'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WorkspaceSearchController::index
* @see app/Http/Controllers/WorkspaceSearchController.php:14
* @route '/search'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkspaceSearchController::index
* @see app/Http/Controllers/WorkspaceSearchController.php:14
* @route '/search'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const WorkspaceSearchController = { index }

export default WorkspaceSearchController