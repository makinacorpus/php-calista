(function ($, Drupal) {
  "use strict";

  Drupal.behaviors.udashboardSearch = {
    attach: function (context, settings) {
      $('.udashboard-search-form', context).once('udashboard-search', function () {
        if(!settings.udashboard || settings.udashboard.search) return;

        // Hide default search and fix size
        $(this).find('input').hide();
        $(this).prepend('<div class="vs"/>');

        var search = VS.init({
          container: $(this).find('.vs'),
          remainder: false,
          autosearch: false,
          callbacks: {
            search: function (query, searchCollection) {
            },
            facetMatches: function (callback) {
              callback(settings.udashboard.search);
            },
            valueMatches: function (facet, searchTerm, callback) {
              // First find the corresponding setting
              var filter = _.findWhere(settings.udashboard.search, {value: facet});

              // Process options
              if (typeof filter.options === "object") {
                var options = _.map(filter.options, function (label, value) {
                  return {label: label, value: value.toString()};
                });
                callback(options);
              }
            }
          }
        });

        // Some integration tweaks
        var width = $(this).find('.vs').width();
        $(this).find('> div').css('float', 'left');
        var inputWidth = $(this).find('> div.input-group').width();
        $(this).find('.vs').width(width - inputWidth);

        // Initialize query from filters from our query string
        var urlParams = Drupal.behaviors.udashboardSearch.urlParams();
        var facets = [];
        _.each(urlParams, function (val, category) {
          if (_.findWhere(settings.udashboard.search, {value: category})) {
            facets.push(new VS.model.SearchFacet({
              category: category,
              value: VS.utils.inflector.trim(val),
              app: search
            }));
          }
        });
        search.searchQuery.reset(facets);

        // Submission handling
        $(this).submit(function () {
          var query = Drupal.behaviors.udashboardSearch.urlParams();

          // First remove all filters from our query string
          _.each(query, function (val, filter) {
            if (_.findWhere(settings.udashboard.search, {value: filter})) {
              delete query[filter];
            }
          });

          // Add search filters
          _.each(search.searchQuery.facets(), function (obj) {
            $.extend(query, obj);
          });
          location.href = location.pathname + '?' + $.param(query);
          return false;
        });
      });
    },

    urlParams: function () {
      var urlParams;
      var match,
        pl = /\+/g,  // Regex for replacing addition symbol with a space
        search = /([^&=]+)=?([^&]*)/g,
        decode = function (s) {
          return decodeURIComponent(s.replace(pl, " "));
        },
        query = window.location.search.substring(1);

      urlParams = {};
      while (match = search.exec(query))
        urlParams[decode(match[1])] = decode(match[2]);
      return urlParams;
    }
  };

})(jQuery, Drupal);
