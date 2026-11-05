import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\WorkspaceActivityController::index
* @see app/Http/Controllers/WorkspaceActivityController.php:10
* @route '/activity'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/activity',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WorkspaceActivityController::index
* @see app/Http/Controllers/WorkspaceActivityController.php:10
* @route '/activity'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WorkspaceActivityController::index
* @see app/Http/Controllers/WorkspaceActivityController.php:10
* @route '/activity'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkspaceActivityController::index
* @see app/Http/Controllers/WorkspaceActivityController.php:10
* @route '/activity'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const WorkspaceActivityController = { index }

export default WorkspaceActivityController