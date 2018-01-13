// Defines Drupal global

declare var Drupal: drupal.Drupal;

declare namespace drupal {
    interface Drupal {
        readonly behaviors: any;
        attachBehaviors(element: Element): void;
    }
}
