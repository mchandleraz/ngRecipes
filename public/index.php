<!doctype html>
<!--[if lt IE 7 ]><html itemscope itemtype="http://schema.org/Product" id="ie6" class="ie ie-old" lang="en-US" prefix="og: http://ogp.me/ns#"><![endif]-->
<!--[if IE 7 ]>   <html itemscope itemtype="http://schema.org/Product" id="ie7" class="ie ie-old" lang="en-US" prefix="og: http://ogp.me/ns#"><![endif]-->
<!--[if IE 8 ]>   <html itemscope itemtype="http://schema.org/Product" id="ie8" class="ie ie-old" lang="en-US" prefix="og: http://ogp.me/ns#"><![endif]-->
<!--[if IE 9 ]>   <html itemscope itemtype="http://schema.org/Product" id="ie9" class="ie" lang="en-US" prefix="og: http://ogp.me/ns#"><![endif]-->
<!--[if gt IE 9]><!--><html itemscope itemtype="http://schema.org/Product" lang="en-US" prefix="og: http://ogp.me/ns#"><!--<![endif]-->
<head>

    <!-- Meta -->
    <meta charset="utf-8">

    <title>ngRecipes</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="yes" name="apple-mobile-web-app-capable">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- Favicons -->
    <link rel="shortcut icon" sizes="16x16 24x24 32x32 48x48 64x64" href="http://scotch.io/favicon.ico">

    <!-- Styles -->
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.2.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.2.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Oswald:400,300|Pathway+Gothic+One">
    <!--[if lt IE 9]>
        <script src="//oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
        <script src="//oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->

    <base href="/">

</head>
<body>

    <div ng-app="elasticRecipes" ng-controller="RecipeCtrl">

        <section class="searchField">
            <form ng-submit="search()">
                <input type="text" ng-model="searchTerm">
                <input type="submit" value="Search for recipes">
            </form>
        </section>

        <section class="results">
            <div class="no-recipes" ng-hide="recipes.length">No results</div>

            <article class="recipe" ng-repeat="recipe in recipes" ng-cloak>
                <h2><a ng-href="recipe.url">{{recipe.name}}</a></h2>
                <ul>
                    <li ng-repeat="ingredient in recipe.ingredients">{{ingredient}}</li>
                </ul>
                <p>
                    {{recipe.description}}
                    <a ng-href="{{recipe.url}}">... More at {{recipe.source}}</a>
                </p>
            </article>

            <div class="load-more" ng-hide="allResults" ng-cloak>
                <a ng-click="loadMore()">More ...</a>
            </div>

        </section>
    </div>

    <!-- Scripts -->
    <script src="bower_components/angularjs/angular.js"></script>
    <script src="bower_components/elasticsearch/elasticsearch.angular.js"></script>

    <script>
        var elasticRecipes = angular.module('elasticRecipes', ['elasticsearch'], ['$locationProvider', function($locationProvider) {
            $locationProvider.html5Mode(true);
        }]);

        elasticRecipes.controller('RecipeCtrl', ['recipeService', '$scope', '$location', function(recipes, $scope, $location){

            var initChoices = [
                'rendang',
                'nasi goreng',
                'pad thai',
                'pizza',
                'lasagne',
                'ice cream',
                'schnitzel',
                'hummou'
            ],
            idx = Math.floor(Math.random() * initChoices.length);

            $scope.recipes = [];        // results
            $scope.page = 0;            // current page
            $scope.allResults = false;  // is this all results?

            // random search term, if one isn't provided
            $scope.searchTerm = $location.search().q || initChoices[idx];

            $scope.search = function() {
                $scope.page = 0;
                $scope.recipes = [];
                $scope.allResults = false;
                $location.search({
                    'q':$scope.searchTerm
                });
                $scope.loadMore();
            };

            $scope.loadMore = function() {
                recipes.search($scope.searchTerm, $scope.page++).then(function(results) {
                    if (results.length !== 10) {
                        $scope.allResults = true;
                    }

                    var ii = 0;

                    for (; ii < results.length; ii++) {
                        $scope.recipes.push(results[ii]);
                    }

                });
            };

            // load some recipes on initial run
            $scope.loadMore();

        }]);

        elasticRecipes.factory('recipeService', ['$q', 'esFactory', '$location', function($q, elasticsearch, $location) {

            var client = elasticsearch({
                host: $location.host() + ':9200'
            });

            /**
             * Given a term and offset, load another 10 recipes
             *
             * returns a promise
             *
             */
            var search = function(term, offset) {
                var deferred = $q.defer(),
                    query = {
                        match: {
                            _all: term
                        }
                    };

                client.search({
                    index: 'recipes',
                    type: 'recipe',
                    body: {
                        size: 10,
                        from: (offset || 0) * 10,
                        query: query
                    }
                }).then(function(result) {
                    var ii = 0,
                        hits_in,
                        hits_out = [];

                    hits_in = (result.hits || {}).hits || [];

                    for (; ii < hits_in.length; ii++) {
                        hits_out.push(hits_in[ii]._source);
                    }

                    deferred.resolve(hits_out);

                }, deferred.reject);

                return deferred.promise;

            };

            return {
                search: search
            };

        }]);
    </script>

</body>
</html>