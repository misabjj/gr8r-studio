import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
/**
* @see \App\Http\Controllers\TaskIndexController::index
* @see app/Http/Controllers/TaskIndexController.php:14
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
* @see app/Http/Controllers/TaskIndexController.php:14
* @route '/tasks'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TaskIndexController::index
* @see app/Http/Controllers/TaskIndexController.php:14
* @route '/tasks'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TaskIndexController::index
* @see app/Http/Controllers/TaskIndexController.php:14
* @route '/tasks'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TaskController::update
* @see app/Http/Controllers/TaskController.php:46
* @route '/tasks/{task}'
*/
export const update = (args: { task: string | number | { id: string | number } } | [task: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/tasks/{task}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\TaskController::update
* @see app/Http/Controllers/TaskController.php:46
* @route '/tasks/{task}'
*/
update.url = (args: { task: string | number | { id: string | number } } | [task: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { task: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { task: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            task: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        task: typeof args.task === 'object'
        ? args.task.id
        : args.task,
    }

    return update.definition.url
            .replace('{task}', parsedArgs.task.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TaskController::update
* @see app/Http/Controllers/TaskController.php:46
* @route '/tasks/{task}'
*/
update.patch = (args: { task: string | number | { id: string | number } } | [task: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\TaskController::favorite
* @see app/Http/Controllers/TaskController.php:21
* @route '/tasks/{task}/favorite'
*/
export const favorite = (args: { task: string | number | { id: string | number } } | [task: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: favorite.url(args, options),
    method: 'patch',
})

favorite.definition = {
    methods: ["patch"],
    url: '/tasks/{task}/favorite',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\TaskController::favorite
* @see app/Http/Controllers/TaskController.php:21
* @route '/tasks/{task}/favorite'
*/
favorite.url = (args: { task: string | number | { id: string | number } } | [task: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { task: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { task: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            task: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        task: typeof args.task === 'object'
        ? args.task.id
        : args.task,
    }

    return favorite.definition.url
            .replace('{task}', parsedArgs.task.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TaskController::favorite
* @see app/Http/Controllers/TaskController.php:21
* @route '/tasks/{task}/favorite'
*/
favorite.patch = (args: { task: string | number | { id: string | number } } | [task: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: favorite.url(args, options),
    method: 'patch',
})

const tasks = {
    index: Object.assign(index, index),
    update: Object.assign(update, update),
    favorite: Object.assign(favorite, favorite),
}

export default tasks