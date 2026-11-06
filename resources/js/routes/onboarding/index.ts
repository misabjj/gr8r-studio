import { queryParams, type RouteQueryOptions, type RouteDefinition } from './../../wayfinder'
/**
* @see \App\Http\Controllers\OnboardingController::index
* @see app/Http/Controllers/OnboardingController.php:10
* @route '/onboarding'
*/
export const index = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

index.definition = {
    methods: ["get","head"],
    url: '/onboarding',
} satisfies RouteDefinition<["get","head"]>

/**
* @see \App\Http\Controllers\OnboardingController::index
* @see app/Http/Controllers/OnboardingController.php:10
* @route '/onboarding'
*/
index.url = (options?: RouteQueryOptions) => {
    return index.definition.url + queryParams(options)
}

/**
* @see \App\Http\Controllers\OnboardingController::index
* @see app/Http/Controllers/OnboardingController.php:10
* @route '/onboarding'
*/
index.get = (options?: RouteQueryOptions): RouteDefinition<'get'> => ({
    url: index.url(options),
    method: 'get',
})

/**
* @see \App\Http\Controllers\OnboardingController::index
* @see app/Http/Controllers/OnboardingController.php:10
* @route '/onboarding'
*/
index.head = (options?: RouteQueryOptions): RouteDefinition<'head'> => ({
    url: index.url(options),
    method: 'head',
})

const onboarding = {
    index: Object.assign(index, index),
}

export default onboarding