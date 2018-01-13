
const REFRESH_URL = 'admin/calista/refresh';

/**
 * Parse query string from the given URL (complete or not)
 *
 * If no "?" char is found, it treats the string as a query string without
 * hostname, invalid entries (without "=" char) will be set as keys in the
 * return object with an empty string value.
 */
function parseLink(uri: string): any {
    if (uri === "") {
        return {};
    }

    const pos = uri.indexOf('?');
    if (-1 !== pos) {
        uri = uri.substr(pos + 1);
    } else {
        return {}; // There is no query
    }

    const ret: any = {};
    uri.split("&").forEach(function(raw) {
        const pos = raw.indexOf("=");
        if (-1 === pos) {
            ret[raw] = "";
        } else {
            const key = raw.substr(0, pos);
            const value = raw.substr(pos + 1);
            ret[key] = decodeURIComponent(value.replace(/\+/g, " "));
        }
  });

  return ret;
}

/**
 * From key/value pairs create a GET parameter string
 */
function createParamString(query: any): string {
    let ret = [];

    for (let key in query) {
        ret.push(encodeURIComponent(key) + '=' + encodeURIComponent(query[key]));
    }

    return ret.join("&");
}

/**
 * From https://davidwalsh.name/javascript-debounce-function
 *   all credits to its original author (rewrote it to be more TS friendly).
 */
function debounce(func: any, wait: number, immediate: boolean = false) {
    let timeout: null | number;

    return function() {
        const context = this;
        const args = arguments;
        const callNow = immediate && !timeout;

        clearTimeout(<number>timeout);

        timeout = setTimeout(() => {
            timeout = null;
            if (!immediate) {
                func.apply(context, args);
            }
        }, wait);

        if (callNow) {
            func.apply(context, args);
        }
    };
};

/**
 * Represents a single page element
 */
export class Page {
    readonly ajaxOnly: boolean = false;
    readonly baseUrl: string = "/";
    readonly element: Element;
    readonly route?: null | string;
    readonly id?: null | string;
    readonly viewType?: null | string;
    readonly searchParam?: null | string;
    readonly modal: HTMLElement;
    refreshing: boolean = false;
    query: any;

    constructor(element: Element) {
        this.element = element;
        this.element.setAttribute("data-page-initialized", "1");

        if (element.hasAttribute("data-page-query")) {
            let value = <string>element.getAttribute("data-page-query");
            try {
                this.query =  JSON.parse(value);
            } catch (error) {
                console.log(`invalid JSON: ${value}`);
                this.query = {};
            }
        }

        this.route = element.getAttribute('data-page-route');
        this.id = element.getAttribute('data-page');
        this.viewType = element.getAttribute('data-view-type');
        this.searchParam = element.getAttribute('data-page-search');
        this.ajaxOnly = element.hasAttribute('data-ajax-only');

        this.modal = document.createElement('div');
        this.modal.setAttribute("class", "page-modal");
        this.element.insertBefore(this.modal, this.element.firstChild);

        this.attachBehaviors(false);
    }

    private request(route: string, data: any): Promise<XMLHttpRequest> {
        return new Promise<XMLHttpRequest>((resolve: (req: XMLHttpRequest) => void, reject: (err: any) => void) => {
            let req = new XMLHttpRequest();
            req.open('GET', this.baseUrl + route + "?" + createParamString(data));
            req.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            req.addEventListener("load", function () {
                if (this.status !== 200) {
                    reject(`${this.status}: ${this.statusText}`)
                } else {
                    resolve(req);
                }
            });
            req.addEventListener("error", function () {
                reject(`${this.status}: ${this.statusText}`);
            });
            req.send();
        });
    }

    /**
     * Redraw blocks from the given response
     */
    private placePageBlocks(response: any): void {
        if (response.query) {
            this.query = response.query;
        }

        if (response.blocks) {
            for (let index in response.blocks) {

                let count = 0;
                for (let block of <Element[]><any>this.element.querySelectorAll(`[data-page-block=${index}]`)) {
                    // Sometime when we have an empty rendering, we end up with " " as
                    // almost empty string, this will disturb jQuery, and we cannot
                    // attach behaviors there.
                    block.innerHTML = <string>response.blocks[index];
                    count++;
                }

                if (count) {
                    if (1 < count) {
                        console.log(`Warning, block ${index} exists more than once in page`);
                    }

                    // And re-attach our own behaviors, they are not targetted properly because of once
                    this.attachBehaviors(true);

                } else {
                    console.log(`Warning, block ${index} does not exists in page`);
                }
            }
        }
    }

    private reload(url: string, error?: any) {
        if (error) {
            console.log(error);
        }
        window.location.href = url;
    }

    /**
     * Spawn modal while loading
     */
    modalSpawn() {
        this.modal.classList.add("loading");
    }

    /**
     * Destroy modal once loaded
     */
    modalDestroy() {
        this.modal.classList.remove("loading");
    }

    /**
     * Refresh the page by sending an AJAX query with the new query
     */
    async refreshPage(query: any, dropAll: boolean = false) {
        // Avoid infinite recursion and multiple orders at the same time
        if (this.refreshing) {
            return;
        }
        this.refreshing = true;

        // Build the new URL to display in the address bar or to redirect to.
        const newUrl = location.pathname + "?" + createParamString(query);

        // No AJAX in a form context.
        if (this.viewType === 'twig_form_page') {
            this.reload(newUrl);
            return;
        }

        this.modalSpawn();

        const data: any = {};
        data._page_id = this.id;
        data._route = this.route;
        // Rebuild correct query data from our state.
        if (!dropAll) {
            for (let key in this.query) {
                data[key] = this.query[key];
            }
        }
        // Then override using the incoming one.
        if (query) {
            for (let key in query) {
                data[key] = query[key];
            }
        }

        this.request(REFRESH_URL, data).then(
            (req) => {
                const response = JSON.parse(req.responseText);
                if (!response) {
                    throw `${req.status}: ${req.statusText}: got invalid response data`;
                }

                this.placePageBlocks(response);
                this.refreshing = false;
                this.modalDestroy();
                //window.history.replaceState({}, document.title, newUrl);
            },
            error => {
                this.reload(newUrl, error);
            }
        ).catch(error => {
            this.reload(newUrl, error);
        });
    }

    /**
     * Re-attach current page page and Drupal behaviours on a replaced block
     */
    attachBehaviors(withDrupalBehavior?: boolean) {
        const target = this;

        // Attach globally Drupal behaviors, we need to do it at the page level
        // else external javascript modules/behaviors will miss whole page, for
        // exemple, dragula based users will not find the container, since we
        // just re-attached the children
        if (withDrupalBehavior) {
            Drupal.attachBehaviors(this.element);
        }

        for (let link of <Element[]><any>this.element.querySelectorAll("[data-page-link]")) {
            link.addEventListener("click", (event: MouseEvent) => {
                event.stopPropagation();
                event.preventDefault();
                target.refreshPage(parseLink(link.getAttribute("href") || ""), true)
            });
        }

        const form = <HTMLElement | null>this.element.querySelector("form.calista-search-form");
        if (form) {

            // Attach automatic input submission, using debounce
            let searchInput = <HTMLInputElement>form.querySelector("input[type=text]");
            if (searchInput) {

                let typeListener = debounce((event: KeyboardEvent) => {
                    const query: any = {};
                    query[this.searchParam || "s"] = searchInput.value;
                    this.refreshPage(query);
                }, 500);

                searchInput.addEventListener("keydown", typeListener);
                searchInput.addEventListener("keypress", typeListener);
                searchInput.addEventListener("change", typeListener);
            }

            // Alter submit behaviour to be AJAXified
            form.addEventListener("submit", (event: Event) => {
                event.stopPropagation();
                event.preventDefault();

                if (searchInput) {
                    const query: any = {};
                    query[this.searchParam || "s"] = searchInput.value;
                    this.refreshPage(query);
                }
            });
        }

        // Ensure there are checkboxes
        const master = <null | HTMLInputElement>this.element.querySelector('[data-page-checkbox="all"]');
        if (master) {
            const checkboxes = <HTMLInputElement[]><any>this.element.querySelectorAll("table input:checkbox");
            if (checkboxes.length) {
                master.addEventListener("click", (event: MouseEvent) => {
                    event.stopPropagation();
                    for (let checkbox of checkboxes) {
                        checkbox.checked = master.checked;
                    }
                });
            }
        }
    }
}
