import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\WorkspaceMembersController::index
* @see app/Http/Controllers/WorkspaceMembersController.php:13
* @route '/members'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/members',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\WorkspaceMembersController::index
* @see app/Http/Controllers/WorkspaceMembersController.php:13
* @route '/members'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\WorkspaceMembersController::index
* @see app/Http/Controllers/WorkspaceMembersController.php:13
* @route '/members'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\WorkspaceMembersController::index
* @see app/Http/Controllers/WorkspaceMembersController.php:13
* @route '/members'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const WorkspaceMembersController = { index }

export default WorkspaceMembersController