import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\MyTasksController::index
* @see app/Http/Controllers/MyTasksController.php:14
* @route '/my-tasks'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/my-tasks',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MyTasksController::index
* @see app/Http/Controllers/MyTasksController.php:14
* @route '/my-tasks'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\MyTasksController::index
* @see app/Http/Controllers/MyTasksController.php:14
* @route '/my-tasks'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MyTasksController::index
* @see app/Http/Controllers/MyTasksController.php:14
* @route '/my-tasks'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const MyTasksController = { index }

export default MyTasksController