/**
 * @license Copyright (c) 2003-2013, CKSource - Frederico Knabben. All rights reserved.
 * For licensing, see LICENSE.html or http://ckeditor.com/license
 */

CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here.
	// For the complete reference:
	// http://docs.ckeditor.com/#!/api/CKEDITOR.config

	// The toolbar groups arrangement, optimized for two toolbar rows.
    
   config.extraPlugins = 'sourcearea';
   config.extraPlugins = 'image';
   config.disableNativeSpellChecker = false;

   config.allowedContent = true;

	// NEXTLOOP - Blank
	config.toolbar_Blank = [
    { name: 'basicstyles', items: ['Maximize'] }
   ];

	// NEXTLOOP - Plain
	config.toolbar_Plain = [
        { name: 'clipboard', items : [ 'Cut','Copy','Paste','Undo','Redo', 'Maximize' ] }
   ];

   
	// NEXTLOOP - Basic
	config.toolbar_Basic = [
    { name: 'basicstyles', items: [ 'Bold', 'Italic','NumberedList','BulletedList','Cut','Copy','Paste','Undo','Redo'] },
	{ name: 'links', items : [ 'Link','Unlink','Anchor', 'Maximize'] }
   ];


	// NEXTLOOP - Advanced
      config.toolbar_Advanced =
    [
        { name: 'clipboard', items : ['Source','Undo','Redo', 'Bold','Italic','Strike','Link','Unlink','Anchor','Image','Table','PageBreak','Styles','Format','NumberedList','BulletedList','-','Maximize'] }
    ];
    
    
      config.toolbar_Custom =
    [
        { name: 'document', items : [ 'NewPage','Preview' ] },
        { name: 'clipboard', items : [ 'Cut','Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
        { name: 'editing', items : [ 'Find','Replace','-','SelectAll','-','Scayt' ] },
        { name: 'insert', items : [ 'Image','Table','HorizontalRule','Smiley','SpecialChar','PageBreak'
                 ,'Iframe' ] },
                '/',
        { name: 'styles', items : [ 'Styles','Format' ] },
        { name: 'basicstyles', items : [ 'Bold','Italic','Strike','-','RemoveFormat' ] },
        { name: 'paragraph', items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote' ] },
        { name: 'links', items : [ 'Link','Unlink','Anchor' ] },
        { name: 'tools', items : [ 'Maximize','-','About' ] }
    ];

	// Remove some buttons, provided by the standard plugins, which we don't
	// need to have in the Standard(s) toolbar.
	config.removeButtons = 'Underline,Subscript,Superscript';

	// Se the most common block elements.
	config.format_tags = 'p;h1;h2;h3;pre';

	// Make dialogs simpler.
	config.removeDialogTabs = 'image:advanced;link:advanced';
	
	config.autoParagraph = false;

config.htmlEncodeOutput = false;
config.entities = false;	
config.entities_additional = '';
	config.enterMode = CKEDITOR.ENTER_BR;

};
