<script crossorigin src="https://unpkg.com/react@16/umd/react.development.js"></script>
<script crossorigin src="https://unpkg.com/react-dom@16/umd/react-dom.development.js"></script>
<link rel="stylesheet" href="https://unpkg.com/graphiql/graphiql.min.css"/>

<div id="graphiql">Loading...</div>

<script src="https://unpkg.com/graphiql/graphiql.min.js" type="application/javascript"></script>
<script>
    function graphQLFetcher(graphQLParams, opts = {headers: {}}) {
        return fetch(
            '<?=\RexGraphQL\RexGraphQL::getURL()?>',
            {
                method: 'post',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    ...opts.headers
                },
                body: JSON.stringify(graphQLParams),
                credentials: 'omit',
            },
        ).then(function (response) {
            return response.json().catch(function () {
                return response.text();
            });
        });
    }

    ReactDOM.render(
        React.createElement(GraphiQL, {
            fetcher: graphQLFetcher,
            headerEditorEnabled: true,
            shouldPersistHeaders: true,
        }),
        document.getElementById('graphiql'),
    );
</script>