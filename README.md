malware-repo
============

**WARNING!** For licensing reason (moved to non open source and purchased librairies) Version 5.1 will be the last open source version of MRF, and will NOT be updated. We have made significant efforts in the price of our pro version, If you wish to upgrade feel free to consider buying a license.  

Malware Repository Framework
Official page: http://www.adlice.com/download/mrf/

## Version 5.1
Refactored pdfdata module, moving to peepdf library (PDFData module)  
Added Submit PDF streams back to the repository (PDFData module)  
Added Download PDF streams (PDFData module)  
Added Define Vendors priority for VirusTotal threat name copy (VT module)  
Added Automatic comment on VirusTotal upload (with config) (VT module)  
Added Raw strings extraction (PEData)  
Added Choose machine when submitting to Cuckoo (Cuckoo module)  
Added Choose options when submitting to Cuckoo (with config) (Cuckoo module)  

## Version 5.0
Complete refactoring with OOP  
Complete refactoring in a modular way  
Complete database schema refactoring (optimized for speed)  
Added "Refresh" button  
Added "Private" property, now owner/admin can lock down a sample to prevent write operations  
Added Imphash (PEData module)  
Added PDB path (PEData module)  
Added Office data module  
Added PDF data module  
Added digisig field into sample view  
Added HexView tab  
Added Statistics page  
Added Cuckoo page  
Changed comments font, now using Courier (fixed width font)  
Replaced search tab by a collapsable box  
Moved threat color decision to server side for better customization  
Fixed a bug in modal dialogs where scrollbars were not used  
Fixed a bug in bulk removal  
Fixed sporadic JS errors breaking the logic  
Removed Quick edit (deprecation)  

## Version 4.3
Added Bulk download  
Added ability to disable modules  
Added ssdeep scan  
Added PE scan  
Added MIME type  
Added program icon  
Added avatar on sample page  
Added PHP7 support  
Added ability to edit uploader 
Added Cuckoo combobox as filter   
Replaced old editor by tinymce editor  
Fixed delete button  
Fixed Cuckoo cron  
Fixed VirusTotal status (added "Not Checked")  
Fixed Github link target  
Fixed responsiveness  
Fixed sample page title  
Fixed dropdown menu on mobile devices  
Fixed URL search  
Fixed Comment truncated at 65k characters  
Fixed favorite filter display  
Fixed URLs display  
Fixed Cuckoo link on sample page  
Fixed CDN links  
Fixed incorrect VirusTotal scan display  
Fixed incorrect Cuckoo scan display  

## Version 4.2
Sample page  
Using new modular installer  
Changed favorite filter for a checkbox  
Added user rights management  
Now comments have a WISIWIG editor  
Added tooltip for bulk selection and favorite  
Added Github project link in the sidebar  
Added URLs description name  
Fixed Signout redirection issue  
Fixed bug when menu wasn't showing because of too few samples  
Fixed overwritting of existing sample  
Fixed menu not showing on IE/Chrome  
Fixed page scrolling on Editing/Save  
Fixed URLs search  
Fixed Comment/URLs refresh when re-opening modal editors

## Version 4.1
Added Clear filters button  
Performance improvments (queries optimizations)  
Fixed a bug preventing upload of archives  
Fixed a bug preventing upload of password protected archives  
Fixed a bug where DOCX and pseudo Zip files where extracted  
Fixed footer link  
Fixed short name with a new config field (on left panel collapsed)  
Fixed comment, url, tags search  
Added URLs filter  
Fixed responsiveness  
Fixed tooltips  
Added avatars in users management menus  

## Version 4.0
New UI, based on AdminLTE  
Using more recent versions of bootstrap and Jquery  

## Version 3.4
Cuckoo: Now you can rescan files  
Cuckoo: Fixed filename (useful for package selection)  
VirusTotal: Fixed filename  
Cuckoo: Added scan parameters in config file  
Fixed a bug preventing comment to be stored  
Fixed VirusTotal uploads with PHP 5.6+  
Fixed Cuckoo uploads with PHP 5.6+  

## Version 3.3
Added URLs to API  
Moved sample comment in meta table (!Breaks backward compatibility!)  
Cuckoo: now storing only database ID instead so that all links are dynamic (!Breaks backward compatibility!)  
Cuckoo: removed unused report field (!Breaks backward compatibility!)  
Cuckoo: compatible with version 2  
Cuckoo: Now able to retrieve and reference old sample reports  

## Version 3.2
Added EULA  
Added cron for VirusTotal and Cuckoo status refresh  
Added URLs sample information  
Added ability to send comment on VirusTotal  
Better tags search and storage  
Added ZIp extraction (no password for now)  
Now comment is displayed/modified into a modal dialog (this allows big comments)  

## Version 3.1
UI fixes  
UI improvements  
Added tags  
Added favorites  
Added more data collapsable row  
Moved some fields into collapsed row  
fixed a lot of bugs  

## Version 3.0
Code reorganization, with now only one config file to change  
Added installer script  
Moved filters into a search tab  
UI tweaks and improvements  

## Version 2.0
Yes, there's no version 1 :)  
Added REST API, even for the UI  
Added Authentication with UserCake. Every user has an API key.  
User can only delete/edit its own samples, unless the user is admin.  
Ability to send samples with REST API, an API key is needed.  
Now samples keep the uploader in database.  
Now samples have editable comment field. Comment can also be sent via API.  
Fixed a lot of bugs.  
Improved UI.  
Added ability to NOT automatically upload to VirusTotal  
Now deployment is easy with the install script  

## Version 0.4
Cuckoo reports are now saved on disk, locally. So that you don't need your cuckoo machine to be up and running to view a report.  
All queries are now properly escaped.  
Added VT score filter.  

## Version 0.3
Added VT re-scan button  
Added Cuckoo support, and cuckoo scan button + results  
Added pagination  
Fixed bugs  

## Version 0.2 
Added Edit button, can change vendor name  
Fixed VT scan when file is unknown  
Now files uploaded are shown first  

## Version 0.1
Initial release  
