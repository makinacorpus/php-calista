(function ($, Drupal) {
  "use strict";

  /**
   * Some general behaviors
   */
  Drupal.behaviors.udashboard = {
    attach: function (context) {
      // Prevent chrome bug with inputs inside anchors
      $('#udashboard-facets', context).find('a input').click(function () {
        location.href = $(this).parents('a').attr('href');
      });
      // @todo temporary deactivating them because they behave wrongly on
      //   user actions: when you click in it, they remain, and it's ugly
      // $('[data-toggle="tooltip"]').tooltip();
    }
  };

  /**
   * Behavior for handling contextual pane and its tabs
   * @type {{attach: Drupal.behaviors.udashboardPane.attach}}
   */
  Drupal.behaviors.udashboardPane = {
    /**
     * Handle tab height
     */
    resizeTabs: function () {
      var $contextualPane = $('#contextual-pane');
      $contextualPane.find('.tabs').height($contextualPane.find('.inner').height() - $contextualPane.find('.actions').height());
    },
    attach: function (context, settings) {
      $(context).find('#contextual-pane').once('udashboardPane', function () {
        var $contextualPane = $('#contextual-pane', context);
        var $toggle = $('#contextual-pane-toggle', context);
        var $page = $('#page', context);

        var initial_size = $contextualPane.css('width');
        $page.css('padding-right', initial_size);

        /**
         * Quick function to determine if pane is hidden.
         * @returns {boolean}
         */
        function paneIsHidden() {
          return $contextualPane.css('margin-right') && $contextualPane.css('margin-right') !== '0px';
        }

        /**
         * Hide or show pane, and toggle link
         */
        function togglePane(shown, fast) {
          $.cookie('contextual-pane-hidden', !shown, {path: '/'});
          var prop = {};
          prop.marginRight = shown ? '0px' : '-' + initial_size;
          if (fast) {
            $contextualPane.css(prop);
            $page.css('padding-right', shown ? initial_size : '15px');
          }
          else {
            $contextualPane.animate(prop);
            $page.animate({paddingRight: shown ? initial_size : '15px'});
          }
        }

        // Action to do on button click
        var $toggle_link = $toggle.find('a');
        $toggle_link.click(function () {
          var $currentLink = $(this);

          // Hide whole pane if current active link is clicked
          if ($currentLink.hasClass('active')) {
            togglePane(false);
            // Update class
            $toggle_link.removeClass('active');
          }
          else {
            // If the pane is hidden, open it
            if (paneIsHidden()) {
              togglePane(true);
            }
            // Change tab status
            $contextualPane.find('.tabs > div').removeClass('active');
            $contextualPane.find('div[id=' + $currentLink.attr('href').substr(1) + ']').addClass('active');

            // Update link's class
            $toggle_link.removeClass('active');
            $currentLink.addClass('active');
            Drupal.behaviors.udashboardPane.resizeTabs();
          }
          return false; // Prevent hash change
        });

        // Initial toggle for default tab
        if ($.cookie('contextual-pane-hidden') && $.cookie('contextual-pane-hidden') !== 'false') {
          // Pane must be hidden
          togglePane(false, true);
          $toggle_link.removeClass('active');
        }
        else {
          $toggle.find('a[href=#tab-' + settings.udashboard.defaultPane + ']').click();
        }

        $(window).resize(Drupal.behaviors.udashboardPane.resizeTabs);
      });
    }
  };

  /**
   * Behavior for handling contextual pane actions
   * @type {{attach: Drupal.behaviors.udashboardPane.attach}}
   */
  Drupal.behaviors.udashboardPaneActions = {
    attach: function (context) {
      $(context).find('#page').once('udashboardPaneActions', function () {
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
        Drupal.behaviors.udashboardPane.resizeTabs();
      });
    },
    detach: function (context) {
      // Destroy all previous buttons
      if ($(context).find('#page').length) {
        var $contextualPane = $('#contextual-pane');
        $contextualPane.find('.actions').find('input[type=submit], button, a.btn').remove();
        $(context).find('#page').removeClass('udashboardPaneActions-processed');
      }
    }
  };
}(jQuery, Drupal));
