import DiscussionList from 'flarum/components/DiscussionList';
import DiscussionsSearchSource from 'flarum/components/DiscussionsSearchSource';

app.initializers.add('flarum-ext-chinese-search', function() {

    DiscussionList.prototype.loadResults = function (offset) {

        const params = this.requestParams();
        params.page = {offset};
        params.include = params.include.join(',');

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
            include: 'relevantPosts,relevantPosts.discussion,relevantPosts.user'
        };

        return app.store.find('xun/discussions', params).then(results => this.results[query] = results);
    }
});