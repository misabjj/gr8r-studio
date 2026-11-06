import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\TaskIndexController::index
* @see app/Http/Controllers/TaskIndexController.php:15
* @route '/tasks'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/tasks',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TaskIndexController::index
* @see app/Http/Controllers/TaskIndexController.php:15
* @route '/tasks'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TaskIndexController::index
* @see app/Http/Controllers/TaskIndexController.php:15
* @route '/tasks'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TaskIndexController::index
* @see app/Http/Controllers/TaskIndexController.php:15
* @route '/tasks'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const TaskIndexController = { index }

export default TaskIndexController