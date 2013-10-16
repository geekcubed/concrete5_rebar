# Rebar
A Generic Object Framework for Concrete5

## Introduction
Firstly, some thanks and credit
* Rebar was initially a fork of, and still is based heavily upon, [C5 Boilerplate CRUD](https://github.com/jordanlev/c5_boilerplate_crud/)
* Attribute Categories are based on those found in [C5 Events Package](https://github.com/francoiscote/C5-Events-Package)
* Rebar includes the Validation library from [Kohana Framework](http://kohanaframework.org/)

Rebar aims to reduce the development time required to implement custom objects when developing using Concrete5.

## Features

## Usage
Include the Rebar folder within the existing libraries folder in your Concrete5 package.  All of the framework can be loaded via

````
Loader::library('rebar_loader', 'your-package');
RebarLoader::loadFramework('your-package');
````

See [Code Templates](https://github.com/geekcubed/concrete5_rebar/tree/master/code_templates) for some quick-start templates, and [Examples](https://github.com/geekcubed/concrete5_rebar/tree/master/examples) for more complete examples of Rebar in action. 


## Status
Rebar is in active development. Use at your own risk!
