# Rebar
A Generic Object Framework for Concrete5

## Introduction
Firstly, some thanks and credit
* Rebar was initially a fork of, and still is based heavily upon, [C5 Boilerplate CRUD](https://github.com/jordanlev/c5_boilerplate_crud/)
* Attribute Categories are based on those found in [C5 Events Package](https://github.com/francoiscote/C5-Events-Package)
* Rebar includes the Validation library from [Kohana Framework](http://kohanaframework.org/)

Rebar aims to reduce the development time required to implement custom objects when developing using Concrete5. As a boiler-plate 
framework, there are no auto-magical "scaffold myModel" commands. You still need to implement (i.e. code) all your Models, Views 
and Controllers yourself.

## Features
### Late Static Binding Models (LSB)
In a nutshell, LSB means that you can set values of static properties in a child class, 
and then access their values inside the parent.

In practice, that means RebarModels are more flexible. Rather than relying on a naming 
convention or pattern, you explicitly set the name of the table and primary key column in your 
model class.

See details at http://uk1.php.net/lsb

###Attributed Models
Rebar fully supports the Concrete5 Attribute/Value system. You can easily add any 
of the default Attribute types (or roll your own) to your models. This means it's very
quick to add drop-down lists of options, addresses etc.


## Requirements
* RebarModel is built around Late Static Bindings. This means PHP5.3+ - ideally 5.4+ for performance gains
* Some of the Controller standards are based on the current version of Concrete5 - so that means 5.5+


## Status
Rebar is in active development. Whilst I am using it in production, others should do so with caution

## TODO
* DisplayTable needs to support sorting / ordering of columns via post-back to ItemList
* ItemList->filterByKeywords() needs completing
