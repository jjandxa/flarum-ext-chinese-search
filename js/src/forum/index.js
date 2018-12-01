import app from 'flarum/app';
import DiscussionList from 'flarum/components/DiscussionList';
import DiscussionsSearchSource from 'flarum/components/DiscussionsSearchSource';

app.initializers.add('flarum-ext-chinese-search', function() {

    DiscussionList.prototype.loadResults = function (offset) {
        const preloadedDiscussions = app.preloadedApiDocument();
        const params = this.requestParams();
        params.page = {offset};
        params.include = params.include.join(',');

        if (preloadedDiscussions && params.filter.q !== undefined && params.filter.q.indexOf('is:') === -1
            && params.filter.q.indexOf('tag:') === -1
            && params.filter.q.indexOf('author:') === -1) {

            const resultData = app.store.find('xun/discussions', params);

            return m.deferred().resolve(resultData).promise;
        } else if (preloadedDiscussions) {
            return m.deferred().resolve(preloadedDiscussions).promise;
        }

        if (params.filter.q !== undefined && params.filter.q.indexOf('is:') === -1
            && params.filter.q.indexOf('tag:') === -1
            && params.filter.q.indexOf('author:') === -1) {

            return app.store.find('xun/discussions', params);
        }
        return app.store.find('discussions', params);
    }

    DiscussionsSearchSource.prototype.search = function (query) {
        query = query.toLowerCase();
        this.results[query] = [];

        const params = {
            filter: {q: query},
            page: {limit: 3},
            include: 'mostRelevantPost'
        };

        return app.store.find('xun/discussions', params).then(results => this.results[query] = results);
    }
});