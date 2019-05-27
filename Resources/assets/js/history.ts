interface State
{
    [id: string]: {[version: number]: any};
}

const state: State = {};

export function getState(id: string, version?: number): any
{
    const value = state[id] && state[id][version || getVersion(id)];

    return value === undefined ? undefined : value;
}

export function setState(id: string, data: any, url?: string, title: string = ''): number
{
    if (!window.history || !window.history.pushState) {
        return 0;
    }

    const versions = history.state || {};
    versions.versions = versions.versions || {};
    versions.versions[id] = versions.versions[id] || 1;
    state[id] = state[id] || {};

    if (url) {
        versions.versions[id]++;
        window.history.pushState(versions, '', url);
    } else {
        window.history.replaceState(versions, '', location.href);
    }

    state[id][versions.versions[id]] = data;

    return versions.versions[id];
}

export function getVersion(id: string): number
{
    return window.history && history.state && history.state.versions && history.state.versions[id] || 0;
}
