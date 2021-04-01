#Finance - Interest Account Library

Finance Interest Account Library provides you as the customer easy to use functions to manipulate your interest account. See the complete lis of methods below.
 
This library implements the PSR-4 interface that you can type-hint against in your own libraries. You can use it in your applications. PSR-12 code standards applied.
 
## Basic Usage
how to use with a php example

## Documentation

### Make commands
A few commands are available for ease of use

#### make install-local
This is to install composer on your local set up

#### make build-docker
Build the docker image which will be used to run tests

#### make test
Run the php unit tests inside the docker image

#### make test-local
Run the php unit tests in your local environment

#### make docs
Create the phpdoc folder with documentation from docblocs

### Usage Instructions
The project is not published anywhere, so it requires a manual integration as a library to your code base. The idea is to publish the library for composer integration.


### Payout information
The ideal way to ensure that payouts are occuring daily, is by creating a php file, which is part a daily running cron job. This file should basically look like the following:
```
$listOfUsers = array( ... );
foreach($listOfUsers as $user)
{
    $UUIDv4Object = new \Finance\IA\Object\UUIDv4Object($uuidExample);
    $FinanceApiRequest = new Finance\IA\Request\FinanceApiRequest($UUIDv4Object);
    $FinanceInterestAccount = new \Finance\IA\Account($UUIDv4Object, $FinanceApiRequest);
    
    $FinanceInterestAccount->openInterestAccount();
    $result = $FinanceInterestAccount->payout();
}
```

#### Example Usage
##### Set up project for use 
Install dependencies
```
composer install
```


In your PHP script, add the following
```
$UUIDv4Object = new \Finance\IA\Object\UUIDv4Object($uuidExample);
$FinanceApiRequest = new Finance\IA\Request\FinanceApiRequest($UUIDv4Object);
$FinanceInterestAccount = new \Finance\IA\Account($UUIDv4Object, $FinanceApiRequest);

# we now can use the library
$FinanceInterestAccount->openInterestAccount($income_in_pennies);
```

### Methods
#### $FinanceInterestAccount->createInterestAccount(int $income)
This method is not fully implementation. The functionality is a potential for future related work.

#### $FinanceInterestAccount->openInterestAccount(int $income)
You need to open the account to ensure that the information is retrieved and set. 

#### $FinanceInterestAccount->listStatement()
Returns a list of transactions objects

#### $FinanceInterestAccount->depositFunds(int $deposit_amount)
Deposit funds in pennies to the opened account. 

#### $FinanceInterestAccount->payout()
This method calculates all the neccessary data and processes how much money is owed to the user. Should it be at least 1 penny, it is deposited to the account. Otherwise, it is stored for accumulation towards future payouts.


### Run unit tests
From root of project
```
./vendor/bin/phpunit
```

## About

### Requirements
- Finance-IA works with PHP 7.4 or above
- PHP-Decimal

## Support or Questions
Contact the author, Chris Bartolo on chris@chrisbartolo.com 

### Author

Chris Bartolo - <chris@chrisbartolo.com><br/>

### Licenses
Open Source - created for the purposes of the Finance Interview process

## Assumptions or Work Required
The task requirements has set a number of functionality that is not provided as part of the API. For this reason, it has been assumed that the following will be implemented:
Future work would include:
* improve handling of exceptions and errors, both for implementation and API endpoints
* simplify tests with re-usable set up code
* simplify financeRequestApi object in Account class

###Statement Endpoint
```
URL: /users/{uuid}/transactions
METHOD: GET
PARAMS: sort=ASC|DESC
RESPONSE: an array/list with transaction objects. Each transaction object has at least date_time, type, amount_in_pennies
```

###Interest Rate GET Endpoint
```
URL: /users/{uuid}/rate
METHOD: GET
RESPONSE: yearly_interest_rate DECIMAL
```

###Interest Rate Update Endpoint
```
URL: /users/{uuid}/rate
METHOD: POST
PARAMS: rate DECIMAL
RESPONSE: true|false
```

###Deposit Endpoint:
```
URL: /users/{uuid}/deposit
METHOD: POST
PARAMS: amount_in_pennies INT
RESPONSE: true|false 
```

###Set Skipped Payout Endpoint
```
URL: /users/{uuid}/skipped_payout
METHOD: POST
PARAMS: amount_in_decimal DECIMAL
RESPONSE: true|false
```

###Get Skipped Payout Endpoint
```
URL: /users/{uuid}/skipped_payout
METHOD: GET
RESPONSE: array of object with field amount_in_decimal
```

###Reset Skipped Payouts Endpoint
```
URL: /users/{uuid}/skipped_payout
METHOD: POST
PARAMS: reset BOOL
RESPONSE: true|false
```

###Store Transaction Endpoint
```
URL: /users/{uuid}/transaction
METHOD: POST
PARAMS: date_time DATE_TIME, concluded BOOL, uuid
RESPONSE: true|false
```

###Total Balance Endpoint
```
URL: /users/{uuid}/balance
METHOD: GET
RESPONSE: balance INT
```