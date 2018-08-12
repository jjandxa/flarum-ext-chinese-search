'use strict';

System.register('jjandxa/flarum-ext-chinese-search/main', ['flarum/components/DiscussionList', 'flarum/components/DiscussionsSearchSource'], function (_export, _context) {
    "use strict";

    var DiscussionList, DiscussionsSearchSource;
    return {
        setters: [function (_flarumComponentsDiscussionList) {
            DiscussionList = _flarumComponentsDiscussionList.default;
        }, function (_flarumComponentsDiscussionsSearchSource) {
            DiscussionsSearchSource = _flarumComponentsDiscussionsSearchSource.default;
        }],
        execute: function () {

            app.initializers.add('flarum-ext-chinese-search', function () {

                DiscussionList.prototype.loadResults = function (offset) {

                    var preloadedDiscussions = app.preloadedDocument();

                    var params = this.requestParams();
                    params.page = { offset: offset };
                    params.include = params.include.join(',');

                    if (preloadedDiscussions && params.filter.q !== undefined && params.filter.q.indexOf('is:') === -1 && params.filter.q.indexOf('tag:') === -1 && params.filter.q.indexOf('author:') === -1) {

                        var resultData = app.store.find('xun/discussions', params);

                        return m.deferred().resolve(resultData).promise;
                    } else if (preloadedDiscussions) {
                        return m.deferred().resolve(preloadedDiscussions).promise;
                    }

                    if (params.filter.q !== undefined && params.filter.q.indexOf('is:') === -1 && params.filter.q.indexOf('tag:') === -1 && params.filter.q.indexOf('author:') === -1) {

                        return app.store.find('xun/discussions', params);
                    }
                    return app.store.find('discussions', params);
                };

                DiscussionsSearchSource.prototype.search = function (query) {
                    var _this = this;

                    query = query.toLowerCase();

                    this.results[query] = [];

                    var params = {
                        filter: { q: query },
                        page: { limit: 3 },
                        include: 'relevantPosts,relevantPosts.discussion,relevantPosts.user'
                    };

                    return app.store.find('xun/discussions', params).then(function (results) {
                        return _this.results[query] = results;
                    });
                };
            });
        }
    };
});