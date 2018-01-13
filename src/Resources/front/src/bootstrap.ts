
import './types/drupal';

import { Pane } from "./pane";
import { Page } from "./page";

declare var document: Document;
declare var window: any;

if (window.Drupal) {
    Drupal.behaviors.calistaPage = {
        attach: function(context: Element, settings: any) {
            for (let element of <Element[]><any>context.querySelectorAll("[data-page]:not([data-page-initialized])")) {
                new Page(element);
            }
        }
    }

    Drupal.behaviors.calistaPane = {
        attach: function(context: Element, settings: any) {
            for (let element of <Element[]><any>context.querySelectorAll("#contextual-pane")) {
                new Pane(element);
            }
        }
    }
} else {
    for (let element of <Element[]><any>document.querySelectorAll("[data-page]:not([data-page-initialized])")) {
        new Page(element);
    }
    for (let element of <Element[]><any>document.querySelectorAll("#contextual-pane")) {
        new Pane(element);
    }
}
