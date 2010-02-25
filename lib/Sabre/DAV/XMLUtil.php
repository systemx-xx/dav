<?php

/**
 * XML utilities for WebDAV
 * 
 * @package Sabre
 * @subpackage DAV
 * @copyright Copyright (C) 2007-2010 Rooftop Solutions. All rights reserved.
 * @author Evert Pot (http://www.rooftopsolutions.nl/) 
 * @license http://code.google.com/p/sabredav/wiki/License Modified BSD License
 */
class Sabre_DAV_XMLUtil {

    /**
     * Returns the 'clark notation' for an element.
     * 
     * For example, and element encoded as:
     * <b:myelem xmlns:b="http://www.example.org/" />
     * will be returned as:
     * {http://www.example.org}myelem
     *
     * This format is used throughout the SabreDAV sourcecode.
     * Elements encoded with the urn:DAV namespace will 
     * be returned as if they were in the DAV: namespace. This is to avoid
     * compatibility problems.
     *
     * This function will return null if a nodetype other than an Element is passed.
     *
     * @param DOMElement $dom 
     * @return string 
     */
    static function toClarkNotation(DOMNode $dom) {

        if ($dom->nodeType !== XML_ELEMENT_NODE) return null;

        // Mapping back to the real namespace, in case it was dav
        if ($dom->namespaceURI=='urn:DAV') $ns = 'DAV:'; else $ns = $dom->namespaceURI;
        
        // Mapping to clark notation
        return '{' . $ns . '}' . $dom->localName;

    }

    /**
     * This method takes an XML document (as string) and converts all instances of the
     * DAV: namespace to urn:DAV
     *
     * This is unfortunately needed, because the DAV: namespace violates the xml namespaces
     * spec, and causes the DOM to throw errors
     */
    static function convertDAVNamespace($xmlDocument) {

        // This is used to map the DAV: namespace to urn:DAV. This is needed, because the DAV:
        // namespace is actually a violation of the XML namespaces specification, and will cause errors
        return preg_replace("/xmlns(:[A-Za-z0-9_]*)?=(\"|\')DAV:(\\2)/","xmlns\\1=\\2urn:DAV\\2",$xmlDocument);

    }

    /**
     * This method provides a generic way to load a DOMDocument for WebDAV use.
     *
     * This method throws a Sabre_DAV_Exception_BadRequest exception for any xml errors.
     * It does not preserve whitespace, and it converts the DAV: namespace to urn:DAV. 
     * 
     * @param string $xml
     * @throws Sabre_DAV_Exception_BadRequest 
     * @return DOMDocument 
     */
    static function loadDOMDocument($xml) {

        if (empty($xml))
            throw new Sabre_DAV_Exception_BadRequest('Empty XML document sent');

        // Retaining old error setting
        $oldErrorSetting =  libxml_use_internal_errors(true);

        // Clearing any previous errors 
        libxml_clear_errors();

        $dom = new DOMDocument();
        $dom->loadXML(self::convertDAVNamespace($xml),LIBXML_NOWARNING | LIBXML_NOERROR);

        // We don't generally care about any whitespace
        $dom->preserveWhiteSpace = false;

        if ($error = libxml_get_last_error()) {
            libxml_clear_errors();
            throw new Sabre_DAV_Exception_BadRequest('The request body had an invalid XML body. (message: ' . $error->message . ', errorcode: ' . $error->code . ', line: ' . $error->line . ')');
        }

        // Restoring old mechanism for error handling
        if ($oldErrorSetting===false) libxml_use_internal_errors(false);

        return $dom;

    }

}
