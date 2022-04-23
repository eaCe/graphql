# :construction: WIP GraphQL für REDAXO 5

Das Addon nutzt [graphql-php](https://github.com/webonyx/graphql-php) und bindet im Backend [GraphiQL](https://github.com/graphql/graphiql) zum testen der Queries ein.
Weiter gibt es eine einfache Möglichkeit Nutzer*innen zu authentifizieren.

Hierfür kann die `login` Mutation genutzt werden:

```
mutation {
  login(user: "user" password: "0123456789") {
    token
    refresh_token
  }
}
```

Der erhaltene Token muss dann im Header übergeben werden:
`Authorization: Bearer <token>`.
Der Zugriff auf Queries oder Mutations kann mit `RexGraphQLAuth::protect($context);` auf angemeldete User beschränkt werden.
Siehe **[UserType](https://github.com/eaCe/graphql/blob/main/lib/Type/UserType.php#L27)**

---

Ein einfaches Beispiel um Artikel und deren Module zu bekommen:

```php
<?php

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use RexGraphQL\RexGraphQL;

$rexModuleType = new ObjectType([
    'name' => 'module',
    'fields' => [
        'id' => Type::id(),
        'key' => Type::id(),
        'name' => Type::id(),
    ],
]);

$rexArticleSliceType = new ObjectType([
    'name' => 'articleSlice',
    'fields' => [
        'id' => Type::id(),
        'module_id' => Type::id(),
        'ctype_id' => Type::id(),
        'module' => [
            'type' => Type::listOf($rexModuleType),
            'resolve' => static function ($articleSlice, array $args, $context, ResolveInfo $info) {
                return rex_sql::factory()->getArray(
                    'SELECT * FROM `rex_module` WHERE `id` = '.$articleSlice['module_id']
                );
            },
        ],
    ],
]);

$rexArticleType = new ObjectType([
    'name' => 'article',
    'fields' => [
        'id' => Type::id(),
        'name' => Type::string(),
        'path' => Type::string(),
        'slice' => [
            'type' => Type::listOf($rexArticleSliceType),
            'resolve' => static function ($article, array $args, $context, ResolveInfo $info) {
                return rex_sql::factory()->getArray(
                    'SELECT * FROM `rex_article_slice` WHERE `article_id` = '.$article['id']
                );
            },
        ],
    ],
]);

$articleQry = [
    'article' => [
        'type' => Type::listOf($rexArticleType),
        'description' => 'Returns all articles',
        'resolve' => static function ($objectValue, array $args, $context, ResolveInfo $info) {
            return rex_sql::factory()->getArray('SELECT * FROM `rex_article`');
        },
    ],
];

RexGraphQL::addQuery($articleQry);
```

Die Query kann dann wie folgt aussehen:

```
query {
  article {
    id
    name
    path
    slice {
      id
      module_id
      ctype_id
      module {
        id
        name
        key
      }
    }
  }
}
```

Und das Resultat:

```
{
  "data": {
    "article": [
      {
        "id": "1",
        "name": "Startseite",
        "path": "|",
        "slice": [
          {
            "id": "8",
            "module_id": "6",
            "ctype_id": "1",
            "module": [
              {
                "id": "6",
                "name": "Hero",
                "key": "hero"
              }
            ]
          },
          {
            "id": "20",
            "module_id": "3",
            "ctype_id": "1",
            "module": [
              {
                "id": "3",
                "name": "Glossary",
                "key": "glossary"
              }
            ]
          },
          {
            "id": "21",
            "module_id": "7",
            "ctype_id": "1",
            "module": [
              {
                "id": "7",
                "name": "Form",
                "key": "form"
              }
            ]
          }
        ]
      },
      {
        "id": "2",
        "name": "Impressum",
        "path": "|",
        "slice": [
          {
            "id": "22",
            "module_id": "1",
            "ctype_id": "1",
            "module": [
              {
                "id": "1",
                "name": "tiny",
                "key": null
              }
            ]
          }
        ]
      },
      [...]
    ]
  }
}
```