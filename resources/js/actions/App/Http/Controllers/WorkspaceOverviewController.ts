import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\WorkspaceOverviewController::index
* @see app/Http/Controllers/WorkspaceOverviewController.php:16
* @route '/overview'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/overview',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WorkspaceOverviewController::index
* @see app/Http/Controllers/WorkspaceOverviewController.php:16
* @route '/overview'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WorkspaceOverviewController::index
* @see app/Http/Controllers/WorkspaceOverviewController.php:16
* @route '/overview'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkspaceOverviewController::index
* @see app/Http/Controllers/WorkspaceOverviewController.php:16
* @route '/overview'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const WorkspaceOverviewController = { index }

export default WorkspaceOverviewController