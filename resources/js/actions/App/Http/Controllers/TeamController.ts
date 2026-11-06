import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../../../wayfinder'
/**
* @see \App\Http\Controllers\TeamController::show
* @see app/Http/Controllers/TeamController.php:58
* @route '/teams/{team}'
*/
export const show = (args: { team: string | number | { id: string | number } } | [team: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/teams/{team}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\TeamController::show
* @see app/Http/Controllers/TeamController.php:58
* @route '/teams/{team}'
*/
show.url = (args: { team: string | number | { id: string | number } } | [team: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { team: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { team: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            team: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        team: typeof args.team === 'object'
        ? args.team.id
        : args.team,
    }

    return show.definition.url
            .replace('{team}', parsedArgs.team.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::show
* @see app/Http/Controllers/TeamController.php:58
* @route '/teams/{team}'
*/
show.get = (args: { team: string | number | { id: string | number } } | [team: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\TeamController::show
* @see app/Http/Controllers/TeamController.php:58
* @route '/teams/{team}'
*/
show.head = (args: { team: string | number | { id: string | number } } | [team: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\TeamController::store
* @see app/Http/Controllers/TeamController.php:22
* @route '/teams'
*/
export const store = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

store.definition = {
    methods: ["post"],
    url: '/teams',
} satisfies RouteDefinition<["post"]>

/**
* @see \App\Http\Controllers\TeamController::store
* @see app/Http/Controllers/TeamController.php:22
* @route '/teams'
*/
store.url = (options?: RouteQueryOptions) => {
    return store.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::store
* @see app/Http/Controllers/TeamController.php:22
* @route '/teams'
*/
store.post = (options?: RouteQueryOptions): RouteDefinition<'post'> => ({
    url: store.url(options),
    method: 'post',
})

/**
* @see \App\Http\Controllers\TeamController::update
* @see app/Http/Controllers/TeamController.php:36
* @route '/teams/{team}'
*/
export const update = (args: { team: string | number | { id: string | number } } | [team: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

update.definition = {
    methods: ["patch"],
    url: '/teams/{team}',
} satisfies RouteDefinition<["patch"]>

/**
* @see \App\Http\Controllers\TeamController::update
* @see app/Http/Controllers/TeamController.php:36
* @route '/teams/{team}'
*/
update.url = (args: { team: string | number | { id: string | number } } | [team: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { team: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { team: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            team: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        team: typeof args.team === 'object'
        ? args.team.id
        : args.team,
    }

    return update.definition.url
            .replace('{team}', parsedArgs.team.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::update
* @see app/Http/Controllers/TeamController.php:36
* @route '/teams/{team}'
*/
update.patch = (args: { team: string | number | { id: string | number } } | [team: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'patch'> => ({
    url: update.url(args, options),
    method: 'patch',
})

/**
* @see \App\Http\Controllers\TeamController::destroy
* @see app/Http/Controllers/TeamController.php:51
* @route '/teams/{team}'
*/
export const destroy = (args: { team: string | number | { id: string | number } } | [team: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

destroy.definition = {
    methods: ["delete"],
    url: '/teams/{team}',
} satisfies RouteDefinition<["delete"]>

/**
* @see \App\Http\Controllers\TeamController::destroy
* @see app/Http/Controllers/TeamController.php:51
* @route '/teams/{team}'
*/
destroy.url = (args: { team: string | number | { id: string | number } } | [team: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { team: args }
    }

    if (typeof args === 'object' && !Array.isArray(args) && 'id' in args) {
        args = { team: args.id }
    }

    if (Array.isArray(args)) {
        args = {
            team: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        team: typeof args.team === 'object'
        ? args.team.id
        : args.team,
    }

    return destroy.definition.url
            .replace('{team}', parsedArgs.team.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\TeamController::destroy
* @see app/Http/Controllers/TeamController.php:51
* @route '/teams/{team}'
*/
destroy.delete = (args: { team: string | number | { id: string | number } } | [team: string | number | { id: string | number } ] | string | number | { id: string | number }, options?: RouteQueryOptions): RouteDefinition<'delete'> => ({
    url: destroy.url(args, options),
    method: 'delete',
})

const TeamController = { show, store, update, destroy }

export default TeamController