**STEAM STATS MANIA**

*NOTE: I AM NOT endorsed or affiliated with STEAM or VALVE. Data gathering
is only intended for personal usage.

Some methods of this implementation will require a large amount of time
and a big number of requests to perform. They should be run only by respecting
the 100 000 requests limit per day as specified in STEAM API terms of use
(the script can be eventually run from the command line).

For operations like getAllAchievmentsForPlayer you should run the 
script with a large max_execution_time (and possible memory_limit), depending on the number
of steam games.

This library can be used, by modifying index.php, to query various
steam methods, or to save some steam data in your database (for the moment
a list of player achievements).

In order to use this implementation, the developer should:  
1)Copy the content of config.php.dist in a new file named config.php,
or simply rename config.php.dist into config.php  
2)In config.php replace the values of $apiKey and $domainName with the
values of API key obtained from steam and the domain used when registered 
developer account.  
3)For the data format, for now only json is available in this implementation, 
so the value of $dataFormat should remain unchanged.  
4)If you intend to use database, you should also add the details related
to database connection (only mysql is supported at this point). Just copy
all the params into config.php, change $useDb value to true, and 
enter db connection details for the other params.
Note that the port is optional. Leave it empty if you don't need to specify it.  
5)Also, dump db structure provided in the repo into your database.
(For the moment only table for players achievements is available).  
@TO DO - extend and optimize db structure 

@TO DO 
Make an additional class for operations related to player
(like getAllAchievmentsForPlayer)