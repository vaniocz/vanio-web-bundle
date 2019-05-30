export function getState(id: string, version?: number): any
{
    const value = window.history && history.state && history.state[id] && history.state[id][version || getVersion(id)];

    return value === undefined ? undefined : value;
}

export function setState(id: string, data: any, url?: string, title: string = ''): number
{
    if (!window.history || !window.history.pushState) {
        return 0;
    }

    const state = history.state || {};
    state.versions = state.versions || {};
    state.versions[id] = state.versions[id] || 1;
    state[id] = state[id] || {};

    if (url) {
        state.versions[id]++;
        state[id][state.versions[id]] = data;
        window.history.pushState(state, '', url);
    } else {
        state[id][state.versions[id]] = data;
        window.history.replaceState(state, '', location.href);
    }

    return state.versions[id];
}

export function getVersion(id: string): number
{
    return window.history && history.state && history.state.versions && history.state.versions[id] || 0;
}
