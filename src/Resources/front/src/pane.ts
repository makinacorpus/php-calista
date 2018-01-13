
class Tab {
    readonly element: HTMLElement;
    readonly linkElement: HTMLElement;
    name: string;

    constructor(element: HTMLElement, linkElement: HTMLElement, name: string) {
        this.element = element;
        this.linkElement = linkElement;
        this.name = name;
    }
}

class PaneState {
    isHidden():boolean {
        return (new RegExp('calista-pane-hidden=1')).test(document.cookie);
    }
    hide() {
        document.cookie = "calista-pane-hidden=1";
    }
    show() {
        document.cookie = "calista-pane-hidden=0";
    }
}

export class Pane {
    readonly element: Element;
    readonly state: PaneState;
    tabs: Tab[];
    displayed: boolean = true;

    constructor(element: Element) {
        this.element = element;
        this.state = new PaneState();

        const toggleLink = <HTMLElement>element.querySelector("#contextual-pane-toggle a");
        toggleLink.addEventListener("click", (event: MouseEvent) => {
            event.stopPropagation();
            event.preventDefault();

            this.togglePane();
            toggleLink.blur();
        });

        this.restoreInitialState();
        this.refresh();

        const defaultTab = element.getAttribute('data-active-tab') || "";
        if (defaultTab) {
            this.toggleTab(defaultTab);
        }
    }

    private async restoreInitialState() {
        // It is supposed to be hidden per default, but I prefer to remain
        // independent from CSS, better be future-proof

        // First disable all animation, in order to avoid the pane to either
        // slowly open or slowly collapse on every page change: it would be
        // a useless an annoying extra animation
        (<HTMLElement>this.element).classList.add("initial-collapse");
        document.body.classList.add("initial-collapse");
        setTimeout(() => {
            (<HTMLElement>this.element).classList.remove("initial-collapse");
            document.body.classList.remove("initial-collapse");
        }, 1000);

        // Deploy the pane
        if (this.state.isHidden()) {
            this.displayed = false;
            this.element.classList.add("contextual-collapsed");
            document.body.classList.remove("calista-body-with-pane");
        } else {
            this.displayed = true;
            this.element.classList.remove("contextual-collapsed");
            document.body.classList.add("calista-body-with-pane");
        }
    }

    async refresh() {
        this.tabs = [];

        for (let linkElement of <HTMLElement[]><any>this.element.querySelectorAll("[data-tab-toggle]")) {

            const tabName = linkElement.getAttribute("data-tab-toggle") || "";
            const tabElement = <HTMLElement | null>this.element.querySelector(`[data-tab=${tabName}]`);

            if (!tabElement) {
                // Tab does not exists, disable the link
                linkElement.setAttribute("disabled", "disabled");
                linkElement.classList.add("disabled");
                linkElement.classList.remove("active");
                continue;
            }

            this.tabs.push(new Tab(tabElement, linkElement, tabName));

            linkElement.addEventListener("click", (event: MouseEvent) => {
                event.stopPropagation();
                event.preventDefault();
                this.toggleTab(tabName);
            });
        }
    }

    async togglePane() {
        if (this.displayed) {
            this.element.classList.add("contextual-collapsed");
            document.body.classList.remove("calista-body-with-pane");
            this.displayed = false;
            this.state.hide();
        } else {
            this.element.classList.remove("contextual-collapsed");
            document.body.classList.add("calista-body-with-pane");
            this.displayed = true;
            this.state.show();
        }
    }

    async toggleTab(name: string) {
        console.log(`contextual pane: toggled tab: ${name}`);

        let activeTab: null | Tab = null;

        for (let tab of this.tabs) {
            if (tab.name === name) {
                activeTab = tab;
            } else {
                tab.element.classList.remove("active");
                tab.linkElement.classList.remove("active");
            }
        }

        if (activeTab) {
            activeTab.element.classList.add("active");
            activeTab.linkElement.classList.add("active");
        } else {
            console.log(`contextual pane: could not find tab: ${name}`);
        }
    }
}

  /**
   * Behavior for handling contextual pane actions
   * @type {{attach: Drupal.behaviors.calistaPane.attach}}
   *
  Drupal.behaviors.calistaPaneActions = {
    attach: function (context) {
      $(context).find('#page').once('calistaPaneActions', function () {
        var $contextualPane = $('#contextual-pane');
        // Get all buttons (link or input) in form-actions
        var $buttons = $('#page .form-actions', context).children('.btn-group, input[type=submit], button, a.btn');
        // Iterate in reverse as they are floated right
        $($buttons.get().reverse()).each(function () {

          $(this).find('input[type=submit], button, a.btn')
            .add($(this).filter('input[type=submit], button, a.btn'))
            .each(function () {
              // Do not hack click if there are events
              if (!$.isEmptyObject($(this).data()) || $(this).is('a')) {
                return;
              }

              // Catch click event and delegate to original
              var originalElem = this;
              $(this).click(function (evt) {
                console.log('old', originalElem);
                console.log('clicked', evt);
                // Simulate click on original element
                if (originalElem !== evt.currentTarget) {
                  $(originalElem).click();
                  return false;
                }
              });
            });

          var $clonedElement = $(this).clone(true);
          $contextualPane.find('.inner .actions').append($clonedElement);
          $contextualPane.find('.dropup').removeClass('dropup');
        });
        Drupal.behaviors.calistaPane.resizeTabs();
      });
    },
    detach: function (context) {
      // Destroy all previous buttons
      if ($(context).find('#page').length) {
        var $contextualPane = $('#contextual-pane');
        $contextualPane.find('.actions').find('input[type=submit], button, a.btn').remove();
        $(context).find('#page').removeClass('calistaPaneActions-processed');
      }
    }
  };
*/
