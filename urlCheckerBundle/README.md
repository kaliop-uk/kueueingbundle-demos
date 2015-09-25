URL Checker Demo
================

This demo shows how to implement a distributed link checker.

It starts with a 'naive' approach and then moves on in successive steps to a fully distributed version.
The goal is to reduce the total time taken by the checks.

A sample list of urls is provided in the bundle, taken from Alexa Top-1M ranking. It contains 1000 urls.
You can use your own url list as well (e.g. if you have little outgoing internet connectivity from the webserver where
you are running the test). If you do, keep in mind that the test works best when the urls to be tested take at least a
few hundred milliseconds each.

There is no web interface in this demo, everything is run from the command line.
The info about the verified urls is stored in a dedicated database table.

## 1. create the database tables:

    php ezpublish\console doc:schema:create


## 2. test the validity of a list of urls, without using the queueing system

In order to execute the validation, a console command has been implemented.
It takes a list of urls as either command-line arguments or from a file, checks each one in a loop using cURL requests,
and stores the data in the database using the Doctrine ORM.

### 2.1 execute the test

    php app\console kaliop_queueing:demo:checkurls -f vendor\kaliop\queueingbundle-demos\urlCheckerBundle\Resources\urllist.txt

### 2.2 check how much time was taken

    php app\console kaliop_queueing:demo:urlstats


## 3 set up queueing broker configuration

### 3.1 install RabbitMQ

Follow instructions at http://www.rabbitmq.com

### 3.2

Import the file vendor\kaliop\queueingbundle\Resources\Config\rabbitmq_sample.yml from one of your configuration files,
or copy its contents into one.


## 4. run the test using 5 workers

In this scenario, the same verification is executed in parallel by 5 worker process.
A new console command is used, which parses the original file, and for each url found, sends a message to the queueing  
system. One of the worker processes then picks up the message and uses it to check a single url.
5 workers processes will execute the checks in parallel.

[...graph...]

## 4.1 set up worker processes configuration

In your app's parameters.yml file, set this up:

    parameters:
        kaliop_queueing.default.workers.list:
            one:
                queue_name: console_command_executor
            two:
                queue_name: console_command_executor
            three:
                queue_name: console_command_executor
            four:
                queue_name: console_command_executor
            five:
                queue_name: console_command_executor

### 4.2 start the workers

    php app\console kaliop_queueing:workerswatchdog start

### 4.3 reset statistics

    php app\console kaliop_queueing:demo:urlstats reset
    
### 4.4 queue the url checks
    
    php app\console kaliop_queueing:demo:distributedcheck console_command -f vendor\kaliop\queueingbundle-demos\urlCheckerBundle\Resources\urllist.txt

### 4.5 check how much time was taken by the test this time
     
         php app\console kaliop_queueing:demo:urlstats

What you will probably find out is that the total time did not improve very much.
The reason is simple: for each url to be checked, the worker process executes a new console command process.
The time taken by the process to start offsets the advantage given by parallel processing. 
The reason for the worker process spinning off a complete new process is that it makes the worker process more robust;
you can think about the worker as a webserver spinning off processes using the cgi interface as a good analogy.


## 5. run the test using 5 workers and batch messages

In this scenario, each message sent via the queueing system is used to check 10 urls instead of 1.
This reduces the number of messages going through the queueing system, but most importantly, it decreases the number of
console commands created to check urls.

[...graph...]

### 5.1 reset statistics

    php app\console kaliop_queueing:demo:urlstats reset
    
### 5.2 queue the url checks
    
    php app\console kaliop_queueing:demo:distributedcheck console_command -b 10 -f vendor\kaliop\queueingbundle-demos\urlCheckerBundle\Resources\urllist.txt

### 5.3 check how much time was taken by the test this time
     
         php app\console kaliop_queueing:demo:urlstats

The time taken for the checking should be quite better than the original.


## 6. run the test using 10 workers and batch messages

In this scenario, each message sent via the queueing system is used to check 10 urls, and we increase the number of
parallel workers to 10.

### 6.1 stop the workers

    php app\console kaliop_queueing:workerswatchdog start

### 6.2 change workers configuration

In your app's parameters.yml file, set this up:

    parameters:
        kaliop_queueing.default.workers.list:
            one:
                queue_name: console_command_executor
            two:
                queue_name: console_command_executor
            three:
                queue_name: console_command_executor
            four:
                queue_name: console_command_executor
            five:
                queue_name: console_command_executor
            six:
                queue_name: console_command_executor
            seven:
                queue_name: console_command_executor
            eight:
                queue_name: console_command_executor
            nine:
                queue_name: console_command_executor
            ten:
                queue_name: console_command_executor

### 6.3 restart the workers

    php app\console kaliop_queueing:workerswatchdog start

### 6.4 reset statistics

    php app\console kaliop_queueing:demo:urlstats reset

### 6.5 reset statistics

    php app\console kaliop_queueing:demo:urlstats reset
    
### 6.6 queue the url checks
    
    php app\console kaliop_queueing:demo:distributedcheck console_command -b 10 -f vendor\kaliop\queueingbundle-demos\urlCheckerBundle\Resources\urllist.txt

### 6.7 check how much time was taken by the test this time
     
         php app\console kaliop_queueing:demo:urlstats

The time taken for the checking should be even better.
