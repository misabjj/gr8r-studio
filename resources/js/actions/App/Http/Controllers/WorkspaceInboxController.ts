import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\WorkspaceInboxController::index
* @see app/Http/Controllers/WorkspaceInboxController.php:12
* @route '/inbox'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/inbox',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WorkspaceInboxController::index
* @see app/Http/Controllers/WorkspaceInboxController.php:12
* @route '/inbox'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WorkspaceInboxController::index
* @see app/Http/Controllers/WorkspaceInboxController.php:12
* @route '/inbox'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkspaceInboxController::index
* @see app/Http/Controllers/WorkspaceInboxController.php:12
* @route '/inbox'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const WorkspaceInboxController = { index }

export default WorkspaceInboxController