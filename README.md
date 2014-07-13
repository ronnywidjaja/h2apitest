# Database configuration

open index.php
Scroll all the way down to find "function getConnection()"

## SQL

DROP TABLE IF EXISTS `questions`, `answers`;

CREATE TABLE `questions` (
  `id` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `type` varchar(100) NOT NULL,
  `responses` text,
  `placeholder` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `answers` (
  `id` varchar(100) NOT NULL,
  `text` text NOT NULL,
  `type` varchar(50) NOT NULL,
  `question_id` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

# Blueprint

FORMAT: 1A

## Question/Answer API
This is a [RESTful](http://en.wikipedia.org/wiki/Representational_state_transfer) [Application Programming Interface (API)](http://en.wikipedia.org/wiki/Application_programming_interface) to the question/answer database.

## Media Types
Where applicable, this API uses the [JSON](http://json-schema.org/documentation.html) media-type to represent resources states and affordances.  All variable names adhere to the [Snake Case](http://en.wikipedia.org/wiki/Snake_case) naming convention.  All resources are documented using the [JSON Schema](http://json-schema.org/examples.html) standard.

Requests with a message-body are using plain JSON to set or update resource states.

## Error States
The common [HTTP Response Status Codes](https://github.com/for-GET/know-your-http-well/blob/master/status-codes.md) should be used.

# API Root [/]
API entry point.

This resource does not have any attributes. Instead it displays an HTML page to indicate the server status.

## GET

+ Response 200 (text/html)

	+ Body

			<html>
			<head><title>API</title>
				<style type="text/css">
					body {
						font-family: Verdana,Helvetica,sans-serif;
						background-color:#FFFFFF;
						font-size: 8pt;
					}
				</style>
			</head>
			<body>
				<h2>API</h2>
				<p> You have successfully connected to the API Server. The Server is operational.</p>
			</body>
			</html>

# Group Questions
Question-related resources of *API*.

## Question [/question/{id}]
A single Question object. The Question resource is the central resource in the API. It represents one question (or input) associated with an individual user.

The Question resource has the following attributes: 

- id
- name - The name of the question (used internally)
- type - The type of question
- responses - An array of responses associated with the question.  Each question must have at least 1 response object.
- placeholder - The prompt or placeholder text that should appear for the question
- created_at

The values *id* and *created_at* are assigned by the API at the moment of creation. 

+ Model (application/json)

    + Body

            {
               "name" : "Gender",
               "id" : "8a754c61c2ced0c5ff79a1827e02c9d643d6d926",
               "responses" : [
                  {
                     "text" : "Male",
                     "type" : "string"
                  },
                  {
                     "text" : "Female",
                     "type" : "string"
                  }
               ],
               "type" : "select"
            }

    + Schema

            {
               "required" : [
                  "type",
                  "name"
               ],
               "title" : "Question",
               "type" : "object",
               "properties" : {
                  "name" : {
                     "minimum" : 1,
                     "maximum" : 255,
                     "type" : "string"
                  },
                  "responses" : {
                     "minitems" : 1,
                     "type" : "array",
                     "unique_items" : true,
                     "items" : {
                        "type" : "string"
                     }
                  },
                  "type" : {
                     "type" : {
                        "enum" : [
                           "checkbox",
                           "currency",
                           "date",
                           "datetimetz",
                           "float",
                           "document",
                           "email",
                           "text",
                           "hyperlink",
                           "url",
                           "integer",
                           "select",
                           "clobtext",
                           "multiselect",
                           "percent",
                           "phone",
                           "richtext",
                           "textarea",
                           "timeofday"
                        ]
                     }
                  },
                  "placeholder" : {
                     "minimum" : 1,
                     "maximum" : 255,
                     "type" : "string"
                  }
               }
            }

### Retrieve a Question [GET]
+ Response 200

    [Question][]
    
### Update a Question [PUT]
To update a Question send JSON data with updated value for one or more of the Question resource attributes. All attributes values (states) from the previous version of this Question are carried over by default if not included in the hash.

+ Request (application/json)

				{
						"name": "Gender"
				}

+ Response 200

    [Question][]

### Delete a Question [DELETE]

+ Response 204

## Question Collection [/questions]
The Question Collection is used to store a new question or retrieve information about existing questions.

### Get Questions [GET]

+ Response 201

        [
            {
                "name": "Gender",
                "id": "8a754c61c2ced0c5ff79a1827e02c9d643d6d926",
                "responses": [
                    {
                        "question_id": "8a754c61c2ced0c5ff79a1827e02c9d643d6d926",
                        "text": "Male",
                        "id": "414be9b2da4b3ddee7e3e0027395b88bd22f6bd4",
                        "type": "boolean"
                    },
                    {
                        "question_id": "8a754c61c2ced0c5ff79a1827e02c9d643d6d926",
                        "text": "Female",
                        "id": "f5e7e98bb42951048528a0e6e9c2bc1fc5e6f1ae",
                        "type": "boolean"
                    }
                ],
                "type": "select"
            },
            {
                "name": "Height (in feet)",
                "id": "1f4a84773a6f6bc4fc9cb84bfd307f46520679a7",
                "responses": [
                    {
                        "minimum": 30,
                        "question_id": "1f4a84773a6f6bc4fc9cb84bfd307f46520679a7",
                        "id": "561b8d5a084a48cd0ddd36b063b38e8408974a33",
                        "type": "integer"
                    }
                ],
                "type": "text",
                "placeholder": "Enter feet"
            }
        ]


### Create a Question [POST]
To add a Question, provide a JSON hash of the *name* and *type* of the question.

+ Request (application/json)

        {
           "name" : "Gender",
           "type" : "select"
        }

+ Response 201
    
        {
           "id" : "8a754c61c2ced0c5ff79a1827e02c9d643d6d926",
           "name" : "Gender",
           "type" : "select"
        }

# Group Answers
Answer-related resources of *API*.

## Answer [/answer/{id}]
A single Answer object.	 One or more Answer resources can be associated with a Question resource.

The Answer resource has the following attributes: 

- id
- text
- type
- question_id
- created_at

The states *id* and *created_at* are assigned by the API at the moment of creation. 

+ Model (application/json)

    + Body

            {
               "question_id" : "8a754c61c2ced0c5ff79a1827e02c9d643d6d926",
               "text" : "Female",
               "id" : "e1fe036a93be57f1b481ec2953ed7853d6804a49",
               "type" : "string"
            }

    + Schema

            {
               "required" : [
                  "type",
                  "question_id"
               ],
               "title" : "Answer",
               "type" : "object",
               "properties" : {
                  "created_at" : {
                     "format" : "date-time",
                     "type" : "string"
                  },
                  "question_id" : {
                     "type" : "string"
                  },
                  "text" : {
                     "minimum" : 1,
                     "maximum" : 255,
                     "type" : "string"
                  },
                  "id" : {
                     "type" : "string"
                  },
                  "type" : {
                     "type" : {
                        "enum" : [
                           "boolean",
                           "integer",
                           "number",
                           "string"
                        ]
                     }
                  }
               }
            }
            

### Get an Answer [GET]
Retrieve an Answer.

+ Parameters

    + id (required, 'string') ... ID of the Answer

+ Response 201

        {
            "question_id" : "8a754c61c2ced0c5ff79a1827e02c9d643d6d926",
            "text" : "Male",
            "id" : "c1e9ca28fdb4218304c6cc24a97e37bb96ddbd47",
            "type" : "string"
        }

### Update an Answer [PUT]
Update an Answer's text.  The *type* and *question_id* may not be changed after creation.

+ Parameters

    + id (required, 'string') ... ID of the Answer

+ Response 201

        {
           "text" : "Woman",
        }

## Answer Collection [/question/{id}/answers]

### Get Answers for a Question [GET]
Retrieve all of the answers for a given Question.

+ Parameters

    + id (required, 'string') ... ID of the Question

+ Response 201

        [
           {
              "question_id" : "8a754c61c2ced0c5ff79a1827e02c9d643d6d926",
              "text" : "Male",
              "id" : "c1e9ca28fdb4218304c6cc24a97e37bb96ddbd47",
              "type" : "string"
           },
           {
              "question_id" : "8a754c61c2ced0c5ff79a1827e02c9d643d6d926",
              "text" : "Female",
              "id" : "e1fe036a93be57f1b481ec2953ed7853d6804a49",
              "type" : "string"
           }
        ]

### Create an Answer to a Question [POST]
To add an Answer to a Question, provide a JSON hash of the *type* and optionally the *text* associated with the response.

+ Parameters

    + id (required, 'string') ... ID of the Question

+ Request (application/json)

        {
           "text" : "Female",
           "type" : "string"
        }

+ Response 201

        {
           "text" : "Female",
           "id" : "88a940b2e8f3dcb2157089fd521f3317782ab18c",
           "type" : "string"
        }

### Remove an Answer to a Question [DELETE]
Remove an Answer associated with a Question.

+ Parameters

    + id (required, 'string') ... ID of the Question

+ Request (application/json)

        {
           "id" : "88a940b2e8f3dcb2157089fd521f3317782ab18c"
        }

+ Response 201

		[Answer][]

