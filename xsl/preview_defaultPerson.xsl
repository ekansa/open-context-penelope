<?xml version="1.0" encoding="utf-8"?><!-- DWXMLSource="../../../Documents and Settings/Eric Kansa/Desktop/atomSample.xml" --><!DOCTYPE xsl:stylesheet  [
	<!ENTITY nbsp   "&#160;">
	<!ENTITY copy   "&#169;">
	<!ENTITY reg    "&#174;">
	<!ENTITY trade  "&#8482;">
	<!ENTITY mdash  "&#8212;">
	<!ENTITY ldquo  "&#8220;">
	<!ENTITY rdquo  "&#8221;"> 
	<!ENTITY pound  "&#163;">
	<!ENTITY yen    "&#165;">
	<!ENTITY euro   "&#8364;">
]>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:gml="http://www.opengis.net/gml" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:georss="http://www.georss.org/georss" xmlns:oc="http://opencontext.org/schema/person_schema_v1.xsd" xmlns:arch="http://ochre.lib.uchicago.edu/schema/Person/Person.xsd" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:dc="http://purl.org/dc/elements/1.1/">
<xsl:output method="xml" indent="yes" encoding="utf-8" doctype-public="-//W3C//DTD XHTML 1.0 Transitional//EN" doctype-system="http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd"/>



<xsl:template name="string-replace">
		<xsl:param name="arg"/>
		<xsl:param name="toReplace"/>
		<xsl:param name="replaceWith"/>
		<xsl:choose>
			<xsl:when test="contains($arg, $toReplace)">
				<xsl:variable name="prefix" select="substring-before($arg, $toReplace)"/>
				<xsl:variable name="postfix" select="substring($arg, string-length($prefix)+string-length($toReplace)+1)"/>
				<xsl:value-of select="concat($prefix, $replaceWith)"/>
				<xsl:call-template name="string-replace">
					<xsl:with-param name="arg" select="$postfix"/>
					<xsl:with-param name="toReplace" select="$toReplace"/>
					<xsl:with-param name="replaceWith" select="$replaceWith"/>
				</xsl:call-template>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$arg"/>
			</xsl:otherwise>
		</xsl:choose>
</xsl:template>



<xsl:template match="/">


<xsl:variable name="badCOINS"><xsl:value-of select="arch:person/oc:metadata/oc:coins"/>
</xsl:variable>
<xsl:variable name="toReplace">Open%20Context</xsl:variable>
<xsl:variable name="replaceWith">Open%20Context&amp;rft.rights=</xsl:variable>

<xsl:variable name="fixedCOINS">
	<xsl:choose>
			<xsl:when test="contains($badCOINS, $toReplace)">
				<xsl:variable name="prefix" select="substring-before($badCOINS, $toReplace)"/>
				<xsl:variable name="postfix" select="substring($badCOINS, string-length($prefix)+string-length($toReplace)+1)"/>
				<xsl:value-of select="concat($prefix, $replaceWith, $postfix)"/>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="$badCOINS"/>
			</xsl:otherwise>
		</xsl:choose>
</xsl:variable>






<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta>
	<xsl:attribute  name="http-equiv">Content-Type</xsl:attribute>
	<xsl:attribute  name="content">text/html; charset=utf-8</xsl:attribute>
</meta>

<title>Open Context view of <xsl:value-of select="arch:person/arch:name/arch:string"/></title>

<link rel="shortcut icon" href="http://www.opencontext.org/open c images/oc_favicon.ico" />


<link href="http://opencontext.org/css/default_banner.css" rel="stylesheet" type="text/css" />
<link href="http://opencontext.org/css/default_person.css" rel="stylesheet" type="text/css" />
<link href="http://opencontext.org/css/opencontext_style.css" rel="stylesheet" type="text/css" />

</head>

<body>

<div id="oc_logo">
	<a href="../" title="Open Context (Home)"><img alt="Open Context Logo" src="http://opencontext.org/images/general/oc_logo.jpg" style="border: none;" ></img></a>
    </div>
    <div id="oc_tagline">
	<img alt="Open Context Tagline" src="http://opencontext.org/images/general/oc_tagline.jpg" ></img>
    </div>
    <div id="oc_beta">
	<img alt="Beta Stamp" src="http://opencontext.org/images/general/oc_betastamp.jpg" ></img>
    </div>
    
    <div id="oc_top_search">

	<form method="get" action="" id="search-form">
	<div id="search_box">
	<input type='text' name='q' class='tinyText' value='Search' size='30' onfocus="if(this.value=='Search')this.value='';" onblur="if(this.value=='')this.value='Search';" />
	</div>
	<div id="search_cntrl">
	    <input class="oc_top_sbutton" type="submit" value="" />
	</div>
	</form>
    </div>

   
   
   <!-- 
    Navigation tabs
    -->    
    
<div id="oc_main_nav">
  <div class="n_act_nav_l"></div>
  <div class="n_act_nav">
    <a href="../" title="Open Context Home Page, map and timeline interface">Home</a>
  </div>
  <div class="n_act_nav_r"></div>
  <div class="n_act_nav_l"></div>
  <div class="n_act_nav">

    <a href="" title="Background, uses, guide for contributors, web services overview">About</a>
  </div>
  <div class="n_act_nav_r"></div>
  <div class="n_act_nav_l"></div>
  <div class="n_act_nav">
    <a href="" title="Summary of datasets in Open Context">Projects</a>
  </div>
  <div class="n_act_nav_r"></div>

  <div class="n_act_nav_l"></div>
  <div class="n_act_nav">
    <a href="" title="Search and browse through locations, contexts, finds, etc.">Browse</a>
  </div>
  <div class="n_act_nav_r"></div>
  <div class="n_act_nav_l"></div>
  <div class="n_act_nav">
    <a href="" title="Search and browse through images linked to Open Context records">Lightbox</a>

  </div>
  <div class="n_act_nav_r"></div>
  <div class="n_act_nav_l"></div>
  <div class="n_act_nav">
    <a href="" title="Tabular data formated for easy download">Tables</a>
  </div>
  <div class="n_act_nav_r"></div>
  <div class="act_nav_l"></div>
  <div class="act_nav">
    <span title="Detailed view of the selected record">Details</span>
  </div>
  <div class="act_nav_r"></div>
  <div class="n_act_nav_l"></div>
  <div class="n_act_nav">
    <span title="Manage your password and notification settings">My Account</span>
  </div>

  <div class="n_act_nav_r"></div>
</div>


<xsl:comment>
BEGIN Container for main page content
</xsl:comment>
<div id="main_page">








<xsl:comment>
BEGIN Container for gDIV of general item information
-
-
-
-
</xsl:comment>

<div id="item_general"> 

    <xsl:comment>
    This is where the item name is displayed
    </xsl:comment>
    <div id="item_name_class"> 
      <div id="item_class_icon"> 
          <img width='40' height='40'> 
            <xsl:attribute name="src">http://opencontext.org/images/item_view/person_icon.jpg</xsl:attribute>
            <xsl:attribute name="alt">Person or Organization</xsl:attribute>
          </img></div>
       <div id="item_name" class="subHeader">Person: <span class="bodyText"><xsl:value-of select="arch:person/arch:name/arch:string"/></span></div>
       <div id="item_class" class="subHeader">Project: <span class="bodyText"><xsl:value-of select="arch:person/oc:metadata/oc:project_name"/></span></div>
    </div>

    <xsl:comment>
    This is where the item views are displayed
    </xsl:comment>
    <div id="viewtrack">
            <div id="item_views" class="bodyText">Number of Views: <strong><xsl:value-of select="arch:person/oc:social_usage/oc:item_views[@type!='spatialCount']/oc:count"/></strong></div>
            <div id="item_last_view" class="tinyText">Project: <a><xsl:attribute name="href">../preview/project?UUID=<xsl:value-of select="arch:person/@ownedBy"/></xsl:attribute><xsl:value-of select="arch:person/oc:metadata/oc:project_name"/></a></div>
    </div>
</div>
<xsl:comment>
END code for General Item info DIV
-
-
-
-
</xsl:comment>
    
    
    <xsl:comment>
    Code for showing the containing context
    </xsl:comment>
    <div id="parent_contexts">
        
    </div>
    <xsl:comment>
    END code for showing the containing context
    </xsl:comment>



<xsl:comment>
Code for showing the database-like content
-
-
-
-
</xsl:comment>
<div id="main_descriptions">

<div id="left_des">

	<div id="preview">
		<p class="subHeader">Content Associated with this Person</p>
		<xsl:if test="//oc:social_usage/oc:item_views[@type='spatialCount']/oc:count !=0">
		<p class="bodyText">Items in these categories have been viewed: <strong><xsl:value-of select="//oc:social_usage/oc:item_views[@type='spatialCount']/oc:count"/></strong> times. (Ranked: <xsl:value-of select="//oc:social_usage/oc:item_views[@type='spatialCount']/oc:count/@rank"/> of  <xsl:value-of select="//oc:social_usage/oc:item_views[@type='spatialCount']/oc:count/@pop"/>)</p>
		</xsl:if>
		<xsl:for-each select="atom:feed/atom:entry">
			<xsl:if test="./atom:category/@term ='category' ">
				<table>
					<tbody>
						<tr>
							<td><a><xsl:attribute name="href"><xsl:for-each select="./atom:link[@rel='alternate']"><xsl:value-of select=".//@href"/></xsl:for-each></xsl:attribute><img><xsl:attribute name="src"><xsl:value-of select="oc:item_class/oc:iconURI"/></xsl:attribute><xsl:attribute name="alt"><xsl:value-of select="oc:item_class/oc:name"/></xsl:attribute></img></a></td><td><strong><a><xsl:attribute name="href"><xsl:for-each select="./atom:link[@rel='alternate']"><xsl:value-of select=".//@href"/></xsl:for-each></xsl:attribute><xsl:value-of select="./atom:title"/></a></strong></td><td><span class="bodyText"><xsl:value-of select="./atom:content"/></span></td>
						</tr>
					</tbody>
				</table>
			</xsl:if>
		</xsl:for-each>
		<xsl:if test="//arch:personInfo/@mediaCount != 0" >
				<p class="bodyText">This person / organization has associated media resources, <a>
														<xsl:attribute name="href">../lightbox?person=<xsl:value-of select="//oc:personContent/oc:pers_Queryval"/></xsl:attribute>(click here to browse)</a>. </p>
		</xsl:if>
	</div>
	
	<div id="properties">
		<p class="subHeader">Description (<xsl:value-of select="count(descendant::arch:person/arch:properties/arch:property)"/> properties)</p>
		<table border="0" cellpadding="1">
			 <xsl:for-each select="arch:person/arch:properties/arch:property">
				  <tr>
					<td width='95'>
						<xsl:value-of select="oc:var_label"/>            </td>
					<td> </td>
					<td>
						<a>
							<xsl:attribute name="href">../preview/prop-check?propUUID=<xsl:value-of select="oc:propid"/></xsl:attribute>
							<xsl:value-of select="oc:show_val"/>                </a>            </td>
				  </tr>
			  </xsl:for-each>
			  <xsl:if test="count(descendant::arch:person/arch:properties/arch:property) = 0">
				<tr><td><xsl:value-of select="arch:person/oc:metadata/oc:no_props" disable-output-escaping="yes"/></td></tr>
			  </xsl:if>
		</table>
	</div>


	


	<div id="allnotes" class="bodyText">
		<p class="subHeader">Person Notes</p>
		<xsl:if test="count(descendant::arch:person/arch:links/oc:diary_links/oc:link) != 0" >	
			<p class="bodyText">
				<xsl:for-each select="arch:person/arch:links/oc:diary_links/oc:link">
				   <a><xsl:attribute name="href">../preview/document?UUID=<xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/></a><xsl:if test="position() != last()"> , </xsl:if>
				</xsl:for-each>
			</p>
		</xsl:if>
		<xsl:if test="count(descendant::arch:person/oc:social_usage/oc:external_references/oc:reference) != 0" >	
			<p class="bodyText"> 
				<xsl:for-each select="arch:person/oc:social_usage/oc:external_references/oc:reference">
				   <a><xsl:attribute name="href"><xsl:value-of select="oc:ref_URI"/></xsl:attribute><em><xsl:value-of select="oc:name" disable-output-escaping="yes"/></em></a>
				   <xsl:if test="position() != last()"> , </xsl:if>
				</xsl:for-each>
			</p>
        </xsl:if>
		
		
		<xsl:if test="count(descendant::arch:person/arch:notes/arch:note) = 0" >
		<p class="bodyText">(There are no additional notes about this person)</p>
		</xsl:if>
		<xsl:for-each select="arch:person/arch:notes/arch:note">
			<p class="bodyText"><xsl:value-of select="arch:string" disable-output-escaping="yes" /></p><br/>
		</xsl:for-each>
	</div>

	<xsl:if test="count(descendant::arch:person/arch:links/oc:space_links/oc:link) != 0" >
		<div id="all_links">
			<p class="subHeader">Linked Items (<xsl:value-of select="count(descendant::arch:person/arch:links/oc:space_links/oc:link)"/> items)</p>
				<xsl:for-each select="arch:person/arch:links/oc:space_links/oc:link">
						<xsl:choose>
							<xsl:when test="position() mod 2 = 1">
								<div class="container_a">
								<div class="container">	
								<a><xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><img> 
									<xsl:attribute name="src">http://www.opencontext.org/database/ui_images/oc_icons/<xsl:value-of select="oc:item_class/oc:iconURI"/></xsl:attribute>
									<xsl:attribute name="alt"><xsl:value-of select="oc:item_class/oc:name"/></xsl:attribute>
								</img></a></div>
								<div class="container"><span class="bodyText"><a>
								<xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/>
								</a> ( <xsl:value-of select="oc:relation"/> )</span></div>
						</div> 
							</xsl:when>
							<xsl:otherwise>
								<div class="clear_container">
								<div class="container">	
								<a><xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><img> 
									<xsl:attribute name="src">http://www.opencontext.org/database/ui_images/oc_icons/<xsl:value-of select="oc:item_class/oc:iconURI"/></xsl:attribute>
									<xsl:attribute name="alt"><xsl:value-of select="oc:item_class/oc:name"/></xsl:attribute>
								</img></a></div>
								<div class="container"><span class="bodyText"><a>
								<xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/>
								</a> ( <xsl:value-of select="oc:relation"/> )</span></div>
						</div> 
							</xsl:otherwise>
						</xsl:choose>
					</xsl:for-each>
					<br/>
					<br/>
			</div>
	</xsl:if>
	
	<div id="all_people" class="bodyText">
		<p class="subHeader">Associated People (<xsl:value-of select="count(descendant::arch:person/arch:links/oc:person_links/oc:link)"/> items)</p>
		<xsl:if test="count(descendant::arch:person/arch:links/oc:person_links/oc:link) != 0" >	
			<p class="bodyText">
				<xsl:for-each select="arch:person/arch:links/oc:person_links/oc:link">
				   <a><xsl:attribute name="href">../persons/<xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/></a> ( <xsl:value-of select="oc:relation"/> )<xsl:if test="position() != last()"> , </xsl:if>
				</xsl:for-each>
			</p>
		</xsl:if>
		<br/>
		<br/>	
	</div>
	
	
</div>

<div id="right_des">

	<div id="all_tags" class="bodyText">
		<p class="subHeader">Tags Made by Other Users  (<xsl:value-of select="count(descendant::atom:category[@term='user tag'])"/>)</p>
		<p class="bodyText">Items associated with this person have been tagged by: <strong><xsl:value-of select="	count(descendant::atom:category[@term='tag creator'])"/></strong> people.</p>
			<xsl:for-each select="atom:feed/atom:entry">
				<xsl:if test="./atom:category/@term ='user tag' ">
				   <p class="bodyText"><a><xsl:attribute name="href"><xsl:for-each select="./atom:link[@rel='related']"><xsl:value-of select=".//@href"/></xsl:for-each></xsl:attribute><xsl:value-of select="./atom:title"/></a></p>
				</xsl:if>
			</xsl:for-each>
		<br/>	
	</div>

	
	<div id="all_media" class="bodyText" align="left">
		<p class="subHeader">Linked Media  (<xsl:value-of select="count(descendant::arch:person/arch:links/oc:media_links/oc:link)"/> files)</p>
		<xsl:if test="count(descendant::arch:person/arch:links/oc:media_links/oc:link) != 0" >
			<table border="0" cellpadding="1">
				<xsl:for-each select="arch:person/arch:links/oc:media_links/oc:link">
						<tr>
							<td>
								<xsl:if test="oc:relation != 'Portrait'">
								<a>
									<xsl:attribute name="href">../media/<xsl:value-of select="oc:id"/></xsl:attribute>
									<xsl:attribute name="title"><xsl:value-of select="oc:name"/></xsl:attribute>
									<img>
										<xsl:attribute name="alt"><xsl:value-of select="oc:name"/></xsl:attribute>
										<xsl:attribute name="src"><xsl:value-of select="oc:thumbnailURI"/></xsl:attribute>
									</img>
								</a>
								</xsl:if>
								<xsl:if test="oc:relation = 'Portrait'">
									<img>
										<xsl:attribute name="alt"><xsl:value-of select="oc:name"/></xsl:attribute>
										<xsl:attribute name="src"><xsl:value-of select="oc:thumbnailURI"/></xsl:attribute>
									</img>
								</xsl:if>
							</td>
						</tr>
				</xsl:for-each>
			</table>
			<br/>
			<br/>
		</xsl:if>
	</div>

</div>



<div id="bottom_des">
<br/>
<br/>



<xsl:comment>
END Code for showing the database-like content
</xsl:comment>
</div>















</div>
<xsl:comment>
END Container for main page content
</xsl:comment>

















<xsl:comment>
BEGIN COINS metadata (for Zotero)
</xsl:comment>

<span class="Z3988">
	<xsl:attribute name="title"><xsl:value-of select="$fixedCOINS"/></xsl:attribute>
</span>

<xsl:comment>
END COINS metadata (for Zotero)
</xsl:comment>



<div id="footer">

<div id="w3c_val_logo">
<a href="http://validator.w3.org/check?uri=referer"><img
        src="http://www.w3.org/Icons/valid-xhtml10-blue"
        alt="Valid XHTML 1.0 Transitional" height="31" width="88" /></a>
</div>


<xsl:comment>
Code for licensing information
</xsl:comment>

<div id="all_lic">
<div id="lic_pict">
<a>
	<xsl:attribute name="href"><xsl:value-of select="arch:person/oc:metadata/oc:copyright_lic/oc:lic_URI"/></xsl:attribute>
	<img width='88' height='31' border='0'> 
	  <xsl:attribute name="src"><xsl:value-of select="arch:person/oc:metadata/oc:copyright_lic/oc:lic_icon_URI"/></xsl:attribute>
	  <xsl:attribute name="alt"><xsl:value-of select="arch:person/oc:metadata/oc:copyright_lic/oc:lic_name"/></xsl:attribute>
	</img>
</a>
</div>

<div class="tinyText" id="licarea"> 
To the extent to which copyright applies, this content is licensed with:<a>
		<xsl:attribute name="rel">license</xsl:attribute>
		<xsl:attribute name="href"><xsl:value-of select="arch:person/oc:metadata/oc:copyright_lic/oc:lic_URI"/></xsl:attribute><xsl:value-of select="arch:person/oc:metadata/oc:copyright_lic/oc:lic_name"/>
            <xsl:value-of select="arch:person/oc:metadata/oc:copyright_lic/oc:lic_vers"/>&#32;License
	</a> Attribution Required: <a href='javascript:showCite()'>Citation</a>, and hyperlinks for online uses.
</div>

</div>
<xsl:comment>
END Code for licensing information
</xsl:comment>



</div>

</div>

<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
</script>
<script type="text/javascript">
_uacct = "UA-4019411-1";
urchinTracker();
</script>
</body>
</html>

</xsl:template>
</xsl:stylesheet>
