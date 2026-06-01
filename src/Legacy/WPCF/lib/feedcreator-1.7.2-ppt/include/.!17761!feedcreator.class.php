<?php
/***************************************************************************

FeedCreator class v1.7.2-ppt
originally (c) Kai Blankenhorn
www.bitfolge.de
kaib@bitfolge.de
v1.3 work by Scott Reynen (scott@randomchaos.com) and Kai Blankenhorn
v1.5 OPML support by Dirk Clemens
v1.7.2-mod on-the-fly feed generation by Fabian Wolf (info@f2w.de)
v1.7.2-ppt ATOM 1.0 support by Mohammad Hafiz bin Ismail (mypapit@gmail.com)

This library is free software; you can redistribute it and/or
modify it under the terms of the GNU Lesser General Public
License as published by the Free Software Foundation; either
version 2.1 of the License, or (at your option) any later version.

This library is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
Lesser General Public License for more details.

You should have received a copy of the GNU Lesser General Public
License along with this library; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

****************************************************************************


Changelog:

v1.7.2-ppt	11-21-05 
	added Atom 1.0 support
	added enclosure support for RSS 2.0/ATOM 1.0
	added docs for v1.7.2-ppt only! 
	
v1.7.2-mod	03-12-05
	added output function outputFeed for on-the-fly feed generation

v1.7.2	10-11-04
	license changed to LGPL

v1.7.1
	fixed a syntax bug
	fixed left over debug code

v1.7	07-18-04
	added HTML and JavaScript feeds (configurable via CSS) (thanks to Pascal Van Hecke)
	added HTML descriptions for all feed formats (thanks to Pascal Van Hecke)
	added a switch to select an external stylesheet (thanks to Pascal Van Hecke)
	changed default content-type to application/xml
	added character encoding setting
