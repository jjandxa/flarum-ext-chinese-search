import app from 'flarum/app';
import {override} from 'flarum/extend';
import DiscussionListState from 'flarum/states/DiscussionListState';
import DiscussionsSearchSource from 'flarum/components/DiscussionsSearchSource';

app.initializers.add('flarum-ext-chinese-search', function () {
  override(DiscussionListState.prototype, 'loadResults', function (original, offset) {
    const preloadedDiscussions = app.preloadedApiDocument();

    if (preloadedDiscussions) {
      return Promise.resolve(preloadedDiscussions);
    }

    const params = this.requestParams();
    params.page = {offset};
    params.include = params.include.join(',');

    if (preloadedDiscussions && params.filter.q !== undefined && params.filter.q.indexOf('is:') === -1
      && params.filter.q.indexOf('tag:') === -1
      && params.filter.q.indexOf('author:') === -1) {
      const resultData = app.store.find('xun/discussions', params);
      return Promise.resolve(resultData);
    } else if (preloadedDiscussions) {
      return Promise.resolve(preloadedDiscussions);
    }

    if (params.filter.q !== undefined && params.filter.q.indexOf('is:') === -1
      && params.filter.q.indexOf('tag:') === -1
      && params.filter.q.indexOf('author:') === -1) {
      return app.store.find('xun/discussions', params);
    }

    return app.store.find('discussions', params);
  })

  override(DiscussionsSearchSource.prototype, 'search', function (original, query) {
    query = query.toLowerCase();
    this.results[query] = [];
    const params = {
      filter: {q: query},
      page: {limit: 3},
      include: 'mostRelevantPost'
    };

    return app.store.find('xun/discussions', params).then((results) => (this.results[query] = results));
  })
});
