import { queryParams, type RouteQueryOptions, type RouteDefinition, applyUrlDefaults } from './../../wayfinder'
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

/**
* @see \App\Http\Controllers\MemberController::show
* @see app/Http/Controllers/MemberController.php:13
* @route '/members/{member}'
*/
export const show = (args: { member: string | number } | [member: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

show.definition = {
    methods: ["get","head"],
    url: '/members/{member}',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\MemberController::show
* @see app/Http/Controllers/MemberController.php:13
* @route '/members/{member}'
*/
show.url = (args: { member: string | number } | [member: string | number ] | string | number, options?: RouteQueryOptions) => {
    if (typeof args === 'string' || typeof args === 'number') {
        args = { member: args }
    }

    if (Array.isArray(args)) {
        args = {
            member: args[0],
        }
    }

    args = applyUrlDefaults(args)

    const parsedArgs = {
        member: args.member,
    }

    return show.definition.url
            .replace('{member}', parsedArgs.member.toString())
            .replace(/\/+$/, '') + queryParams(options)
}

/**
* @see \App\Http\Controllers\MemberController::show
* @see app/Http/Controllers/MemberController.php:13
* @route '/members/{member}'
*/
show.get = (args: { member: string | number } | [member: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: show.url(args, options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\MemberController::show
* @see app/Http/Controllers/MemberController.php:13
* @route '/members/{member}'
*/
show.head = (args: { member: string | number } | [member: string | number ] | string | number, options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: show.url(args, options),
    method: 'head',
})

const members = {
    index: Object.assign(index, index),
    show: Object.assign(show, show),
}

export default members