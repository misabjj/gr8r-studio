import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\SystemController::design
* @see app/Http/Controllers/SystemController.php:10
* @route '/design-system'
*/
export const design = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: design.url(options),
    method: 'get',
})

design.definition = {
    methods: ["get","head"],
    url: '/design-system',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SystemController::design
* @see app/Http/Controllers/SystemController.php:10
* @route '/design-system'
*/
design.url = (options?: RouteQueryOptions) => {
    return design.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SystemController::design
* @see app/Http/Controllers/SystemController.php:10
* @route '/design-system'
*/
design.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: design.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SystemController::design
* @see app/Http/Controllers/SystemController.php:10
* @route '/design-system'
*/
design.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: design.url(options),
    method: 'head',
})

/**
* @see \App\Http\Controllers\SystemController::states
* @see app/Http/Controllers/SystemController.php:15
* @route '/system-states'
*/
export const states = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: states.url(options),
    method: 'get',
})

states.definition = {
    methods: ["get","head"],
    url: '/system-states',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\SystemController::states
* @see app/Http/Controllers/SystemController.php:15
* @route '/system-states'
*/
states.url = (options?: RouteQueryOptions) => {
    return states.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\SystemController::states
* @see app/Http/Controllers/SystemController.php:15
* @route '/system-states'
*/
states.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: states.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\SystemController::states
* @see app/Http/Controllers/SystemController.php:15
* @route '/system-states'
*/
states.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: states.url(options),
    method: 'head',
})

const system = {
    design: Object.assign(design, design),
    states: Object.assign(states, states),
}

export default system