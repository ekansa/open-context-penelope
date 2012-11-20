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
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:gml="http://www.opengis.net/gml" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:georss="http://www.georss.org/georss" xmlns:oc="http://opencontext.org/schema/space_schema_v1.xsd" xmlns:arch="http://ochre.lib.uchicago.edu/schema/SpatialUnit/SpatialUnit.xsd" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:dc="http://purl.org/dc/elements/1.1/">
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


<xsl:variable name="badCOINS"><xsl:value-of select="arch:spatialUnit/oc:metadata/oc:coins"/>
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

<xsl:variable name="num_contribs">
	<xsl:value-of select="count(arch:spatialUnit/oc:metadata/dc:contributor)"/>
</xsl:variable>

<xsl:variable name="num_editors">
	<xsl:value-of select="count(arch:spatialUnit/oc:metadata/dc:creator)"/>
</xsl:variable>

<xsl:variable name="num_externalRefs">
	<xsl:value-of select="count(//oc:external_references/oc:reference)"/>
</xsl:variable>

<xsl:variable name="num_Obs">
	<xsl:value-of select="count(//arch:observations/arch:observation)"/>
</xsl:variable>

<xsl:variable name="num_Children">
	<xsl:value-of select="count(//arch:spatialUnit/oc:children/oc:tree/oc:child)"/>
</xsl:variable>

<xsl:variable name="max_Tabs">10</xsl:variable>

<xsl:variable name="citation">
	<xsl:for-each select="arch:spatialUnit/oc:metadata/dc:contributor">
		<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
	</xsl:for-each>
	<xsl:if test="$num_contribs = 0"> 
		<xsl:for-each select="arch:spatialUnit/oc:metadata/dc:creator">
			<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
		</xsl:for-each>
	</xsl:if> &quot;<span xmlns:dc="http://purl.org/dc/elements/1.1/" property="dc:title"><xsl:value-of select="arch:spatialUnit/oc:metadata/dc:title"/></span>&quot; (Released <xsl:value-of select="arch:spatialUnit/oc:metadata/dc:date"/>). <xsl:for-each select="arch:spatialUnit/oc:metadata/dc:creator"> <xsl:value-of select="."/><xsl:if test="position() != last()">, </xsl:if><xsl:if test="position() = last()"><xsl:if test="$num_editors = 1"> (Ed.) </xsl:if><xsl:if test="$num_editors != 1"> (Eds.) </xsl:if></xsl:if></xsl:for-each> <em>Open Context. </em>  
</xsl:variable>

<xsl:variable name="citationView">
	<xsl:for-each select="arch:spatialUnit/oc:metadata/dc:contributor">
		<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
	</xsl:for-each>
	<xsl:if test="$num_contribs = 0"> 
		<xsl:for-each select="arch:spatialUnit/oc:metadata/dc:creator">
			<xsl:value-of select="."/>
		<xsl:if test="position() != last()">, </xsl:if>
		<xsl:if test="position() = last()">. </xsl:if>
		</xsl:for-each>
	</xsl:if>&quot;<xsl:value-of select="arch:spatialUnit/oc:metadata/dc:title"/>&quot; (Released <xsl:value-of select="arch:spatialUnit/oc:metadata/dc:date"/>). <xsl:for-each select="arch:spatialUnit/oc:metadata/dc:creator"> <xsl:value-of select="."/><xsl:if test="position() != last()">, </xsl:if><xsl:if test="position() = last()"><xsl:if test="$num_editors = 1"> (Ed.) </xsl:if><xsl:if test="$num_editors != 1"> (Eds.) </xsl:if></xsl:if></xsl:for-each> <em>Open Context. </em> &lt;http://opencontext.org/subjects/<xsl:value-of select="arch:spatialUnit/@UUID"/>&gt; 
</xsl:variable>





<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<meta>
	<xsl:attribute  name="http-equiv">Content-Type</xsl:attribute>
	<xsl:attribute  name="content">text/html; charset=utf-8</xsl:attribute>
</meta>

<title>Open Context view of <xsl:value-of select="//dc:title"/></title>

<link rel="shortcut icon" href="/images/general/oc_favicon.ico" type="image/x-icon" />
<link rel="alternate" type="application/atom+xml">
<xsl:attribute name="title">Atom feed: <xsl:value-of select="//dc:title"/></xsl:attribute>
<xsl:attribute name="href">../subjects/<xsl:value-of select="arch:spatialUnit/@UUID"/>.atom</xsl:attribute>
</link>
<link rel="unapi-server" type="application/xml" title="unAPI" href="http://opencontext.org/unapi" />
<link href="/css/default_banner.css" rel="stylesheet" type="text/css" />
<link href="/css/default_spatial.css" rel="stylesheet" type="text/css" />
<link href="/css/opencontext_style.css" rel="stylesheet" type="text/css" />
<link href="/css/default_spatial_rdfa.css" rel="stylesheet" type="text/css" />
<link href="/css/tabber.css" rel="stylesheet" type="text/css" />
<script type="text/javascript"> 
 
<!-- 

document.write('<style type="text/css">.tabber{display:none;}<\/style>');
 
var tabberOptions = {manualStartup:true};

-->
</script> 
 
<script type="text/javascript" src="/js/tabber.js"></script>
</head>

<body>

<div id="oc_logo">
	<a href="../" title="Open Context (Home)"><img alt="Open Context Logo" src="/images/general/oc_logo.jpg" style="border: none;" ></img></a>
    </div>
    <div id="oc_tagline">
	<img alt="Open Context Tagline" src="/images/general/oc_tagline.jpg" ></img>
    </div>
    <div id="oc_beta">
	<img alt="Beta Stamp" src="/images/general/oc_betastamp.jpg" ></img>
    </div>
    
    <div id="oc_top_search">

	<form method="get" action="../sets/" id="search-form">
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

    <a href="../about/" title="Background, uses, guide for contributors, web services overview">About</a>
  </div>
  <div class="n_act_nav_r"></div>
  <div class="n_act_nav_l"></div>
  <div class="n_act_nav">
    <a href="../projects/" title="Summary of datasets in Open Context">Projects</a>
  </div>
  <div class="n_act_nav_r"></div>

  <div class="n_act_nav_l"></div>
  <div class="n_act_nav">
    <a href="../sets/" title="Search and browse through locations, contexts, finds, etc.">Browse</a>
  </div>
  <div class="n_act_nav_r"></div>
  <div class="n_act_nav_l"></div>
  <div class="n_act_nav">
    <a href="../lightbox/" title="Search and browse through images linked to Open Context records">Lightbox</a>

  </div>
  <div class="n_act_nav_r"></div>
  <div class="n_act_nav_l"></div>
  <div class="n_act_nav">
    <a href="http://opencontext.org/tables/" title="Tabular data formated for easy download">Tables</a>
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
            <xsl:attribute name="src"><xsl:value-of select="arch:spatialUnit/oc:item_class/oc:iconURI"/></xsl:attribute>
            <xsl:attribute name="alt"><xsl:value-of select="arch:spatialUnit/oc:item_class/oc:name"/></xsl:attribute>
          </img>       </div>
       <div id="item_name" class="subHeader">Item: <xsl:value-of select="arch:spatialUnit/arch:name/arch:string"/></div>
       <div id="item_class" class="subHeader">Class: <xsl:value-of select="//arch:spatialUnit/oc:item_class/oc:name"/></div>
    </div>

    <xsl:comment>
    This is where the item views are displayed
    </xsl:comment>
    <div id="viewtrack">
            <div id="item_views" class="bodyText">Number of Views: <strong><xsl:value-of select="arch:spatialUnit/oc:social_usage/oc:item_views/oc:count"/></strong></div>
            <div id="item_last_view" class="tinyText">Project: <a><xsl:attribute name="href">../projects/<xsl:if test="arch:spatialUnit/@ownedBy !=0"><xsl:value-of select="arch:spatialUnit/@ownedBy"/></xsl:if></xsl:attribute><xsl:value-of select="arch:spatialUnit/oc:metadata/oc:project_name"/></a></div>
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
        <div class="subHeader" id="contexttitle" style="text-align:left;">Context (click to view): </div>
        <div id="pcontext" class="bodyText" style="text-align:left;">
        
                
        
                    <xsl:for-each select="arch:spatialUnit/oc:context/oc:tree[@id='default']/oc:parent">
                       
                        <a>
				<xsl:choose>
						<xsl:when test="position() = last()">
								<xsl:attribute name="rel">conc:findspot</xsl:attribute>
						</xsl:when>
				</xsl:choose>
                            <xsl:attribute name="href"><xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/>                        </a>
                    <xsl:if test="position() != last()"> / </xsl:if>
                    </xsl:for-each>
               
        </div>
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
        
	<div>
	<xsl:choose>
		<xsl:when test="$num_Obs != 1">
				<xsl:attribute name="id">tabber</xsl:attribute>
				<xsl:attribute name="class">tabber</xsl:attribute>
				<xsl:attribute name="style"></xsl:attribute>
		</xsl:when>
		<xsl:otherwise>
		</xsl:otherwise>
	</xsl:choose>
	
		<xsl:for-each select="arch:spatialUnit/arch:observations/arch:observation">
				<xsl:if test="(position() &lt; $max_Tabs) or (((@obsNumber = '100') or (@obsNumber &lt; '0') or (oc:obs_metadata/oc:type = 'Preliminary')) and (position() = $max_Tabs))">
				<xsl:choose>
						<xsl:when test="(@obsNumber != '100') and ((@obsNumber &lt; '0') or (oc:obs_metadata/oc:type = 'Preliminary')) ">
								<xsl:call-template name="obsShow">
										<xsl:with-param name="totalObs" select="$num_Obs"/>
										<xsl:with-param name="obsPos" select="position()"/>
										<xsl:with-param name="notCurrent" select="1"/>
										<xsl:with-param name="citationView" select="$citationView"/>
										<xsl:with-param name="num_externalRefs" select="$num_externalRefs"/>
										<xsl:with-param name="max_Tabs" select="$max_Tabs"/>
								</xsl:call-template>
						</xsl:when>
						<xsl:when test="(@obsNumber = '100')">
								
						</xsl:when>
						<xsl:otherwise>
								<xsl:call-template name="obsShow">
										<xsl:with-param name="totalObs" select="$num_Obs"/>
										<xsl:with-param name="obsPos" select="position()"/>
										<xsl:with-param name="notCurrent" select="0"/>
										<xsl:with-param name="citationView" select="$citationView"/>
										<xsl:with-param name="num_externalRefs" select="$num_externalRefs"/>
										<xsl:with-param name="max_Tabs" select="$max_Tabs"/>
								</xsl:call-template>
						</xsl:otherwise>
				</xsl:choose>
				</xsl:if>
		</xsl:for-each>
		<xsl:if test="$num_Obs &gt; $max_Tabs">
				<div><xsl:attribute name="class">tabbertab</xsl:attribute><xsl:attribute name="title">Obs. <xsl:value-of select="$max_Tabs"/>+</xsl:attribute>
				<xsl:for-each select="arch:spatialUnit/arch:observations/arch:observation">
				<xsl:if test="(position() &gt;= $max_Tabs)">
				<xsl:choose>
						<xsl:when test="(@obsNumber != '100') and ((@obsNumber &lt; '0') or (oc:obs_metadata/oc:type = 'Preliminary'))">
								<xsl:call-template name="obsShow">
										<xsl:with-param name="totalObs" select="$num_Obs"/>
										<xsl:with-param name="obsPos" select="position()"/>
										<xsl:with-param name="notCurrent" select="1"/>
										<xsl:with-param name="citationView" select="$citationView"/>
										<xsl:with-param name="num_externalRefs" select="$num_externalRefs"/>
										<xsl:with-param name="max_Tabs" select="$max_Tabs"/>
								</xsl:call-template>
						</xsl:when>
						<xsl:when test="(@obsNumber = '100')">
								
						</xsl:when>
						<xsl:otherwise>
								<xsl:call-template name="obsShow">
										<xsl:with-param name="totalObs" select="$num_Obs"/>
										<xsl:with-param name="obsPos" select="position()"/>
										<xsl:with-param name="notCurrent" select="0"/>
										<xsl:with-param name="citationView" select="$citationView"/>
										<xsl:with-param name="num_externalRefs" select="$num_externalRefs"/>
										<xsl:with-param name="max_Tabs" select="$max_Tabs"/>
								</xsl:call-template>
						</xsl:otherwise>
				</xsl:choose>
				</xsl:if>
				</xsl:for-each>				
				</div>
		</xsl:if>
	
	
	
	</div>
	<!--last div of observations related content -->
	
	<xsl:if test="$num_Children != 0">
	<div id="allchildren">
		<p class="subHeader">Contents (<xsl:value-of select="count(descendant::arch:spatialUnit/oc:children/oc:tree/oc:child)"/> items)</p>
		<xsl:if test="count(descendant::arch:spatialUnit/oc:children/oc:tree/oc:child) != 0" >
					<xsl:for-each select="arch:spatialUnit/oc:children/oc:tree/oc:child">
						<xsl:if test="oc:name != ''">
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
										</a> (<xsl:value-of select="oc:item_class/oc:name"/>)</span></div>
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
												</a> (<xsl:value-of select="oc:item_class/oc:name"/>)</span></div>
										</div> 
										</xsl:otherwise>
								</xsl:choose>
						</xsl:if>
					</xsl:for-each>
					<br/>
					<br/>
        
		</xsl:if>
        
        </div>
	</xsl:if>

</div>
<div id="right_des">

	<div id="all_tags" class="bodyText rounded-corners">
		<p class="subHeader">Descriptive Tags  (<xsl:value-of select="count(descendant::arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag[@status='public'])"/>)</p>
			<p class="bodyText">
				<xsl:for-each select="arch:spatialUnit/oc:social_usage/oc:user_tags/oc:tag">
				   
				   <a>
						<xsl:if test="@type != 'chronological'"><xsl:attribute name="href">../sets/?tag[]=<xsl:value-of select="oc:name"/></xsl:attribute></xsl:if>
						<xsl:if test="@type = 'chronological'"><xsl:attribute name="href">../sets/?t-start=<xsl:value-of select="//oc:time_start"/>&amp;t-end=<xsl:value-of select="//oc:time_finish"/></xsl:attribute><xsl:attribute name="title">Rough dates provided by editors to facilitate searching</xsl:attribute></xsl:if><xsl:value-of select="oc:name"/></a><xsl:if test="position() != last()"> , </xsl:if>
				</xsl:for-each>
			</p>
				<xsl:if test="//oc:user_tags/oc:tag[@type = 'chronological']">
						<p class="tinyText"><strong>Editor's Note:</strong> Date ranges are approximate and do not necessarily reflect the opinion of data contributors. These dates are provided only to facilitate searches.</p>
				</xsl:if>
		<br/>	
	</div>

	
	<div id="all_media" class="bodyText rounded-corners" align="left">
		<p class="subHeader">Linked Media  (<xsl:value-of select="count(descendant::arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:media_links/oc:link)"/> files)</p>
		<xsl:if test="count(descendant::arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:media_links/oc:link) != 0" >
			<table border="0" cellpadding="1">
				<xsl:for-each select="arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:media_links/oc:link">
						<tr>
							<td>
								<a>
									<xsl:attribute name="href">../media/<xsl:value-of select="oc:id"/></xsl:attribute>
									<xsl:attribute name="title"><xsl:value-of select="oc:name"/></xsl:attribute>
									<img>
										<xsl:attribute name="alt"><xsl:value-of select="oc:name"/></xsl:attribute>
										<xsl:attribute name="src"><xsl:value-of select="oc:thumbnailURI"/></xsl:attribute>
									</img>
								</a>
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



</div>
<!--end tag for main page -->



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
        src="http://www.w3.org/Icons/valid-xhtml-rdfa"
        alt="Valid XHTML + RDFa" height="31" width="88" /></a>
</div>


<xsl:comment>
Code for licensing information
</xsl:comment>

<div id="all_lic">
<div id="lic_pict">
<a>
	<xsl:attribute name="href"><xsl:value-of select="arch:spatialUnit/oc:metadata/oc:copyright_lic/oc:lic_URI"/></xsl:attribute>
	<img width='88' height='31' style='border:none;'> 
	  <xsl:attribute name="src"><xsl:value-of select="arch:spatialUnit/oc:metadata/oc:copyright_lic/oc:lic_icon_URI"/></xsl:attribute>
	  <xsl:attribute name="alt"><xsl:value-of select="arch:spatialUnit/oc:metadata/oc:copyright_lic/oc:lic_name"/></xsl:attribute>
	</img>
</a>
</div>

<div class="tinyText" id="licarea"> 
To the extent to which copyright applies, this content is licensed with:<a>
		<xsl:attribute name="rel">license</xsl:attribute>
		<xsl:attribute name="href"><xsl:value-of select="arch:spatialUnit/oc:metadata/oc:copyright_lic/oc:lic_URI"/></xsl:attribute><xsl:value-of select="arch:spatialUnit/oc:metadata/oc:copyright_lic/oc:lic_name"/>
            <xsl:value-of select="arch:spatialUnit/oc:metadata/oc:copyright_lic/oc:lic_vers"/>&#32;License
	</a> Attribution Required: <a href='javascript:showCite()'>Citation</a>, and hyperlinks for online uses.
	<div style="width:0px; overflow:hidden;">
		<abbr class="unapi-id"><xsl:attribute name="title"><xsl:value-of select="arch:spatialUnit/oc:metadata/dc:identifier"/></xsl:attribute><xsl:value-of select="arch:spatialUnit/oc:metadata/dc:identifier"/></abbr>
		<a xmlns:cc="http://creativecommons.org/ns#">
			<xsl:attribute name="href"><xsl:value-of select="arch:spatialUnit/oc:metadata/dc:identifier"/></xsl:attribute>
			<xsl:attribute name="property">cc:attributionName</xsl:attribute>
			<xsl:attribute name="rel">cc:attributionURL</xsl:attribute>
			<xsl:value-of select="$citation"/>
		</a>
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


<xsl:template name="obsShow">
  
		<xsl:param name="totalObs" select="1"/>
		<xsl:param name="obsPos" select="1"/>
		<xsl:param name="notCurrent" select="0"/>
		<xsl:param name="citationView" select="0"/>
		<xsl:param name="num_externalRefs" select="0"/>
		<xsl:param name="max_Tabs" select="10"/>
		<xsl:param name="propBaseURI">../properties/</xsl:param>
		
		<div xmlns="http://www.w3.org/1999/xhtml">
				<xsl:choose>
				<xsl:when test="$totalObs = 1">
						<xsl:attribute name="style">margin-top:10px;</xsl:attribute>
				</xsl:when>
				<xsl:when test="(($notCurrent = 1) and ($totalObs &lt; $max_Tabs)) or (($notCurrent = 1) and ($max_Tabs = $obsPos))">
						<!-- <xsl:attribute name="class">hideObs rounded-corners</xsl:attribute> -->
						<xsl:attribute name="class">tabbertab</xsl:attribute>
						<xsl:attribute name="title">(Prelim. Version)</xsl:attribute>
				</xsl:when>
				<xsl:when test="$obsPos = 1">
						<!-- <xsl:attribute name="class">hideObs rounded-corners</xsl:attribute> -->
						<xsl:attribute name="class">tabbertab</xsl:attribute>
						<xsl:attribute name="title">Main Obs.</xsl:attribute>
				</xsl:when>
				<xsl:when test="($obsPos &gt;= $max_Tabs) and ($totalObs &gt; $max_Tabs)">
						<xsl:attribute name="class">obs rounded-corners</xsl:attribute>
						<xsl:if test="$obsPos mod 2 = 1">
								<xsl:attribute name="style">background-color:#F8F8F8;</xsl:attribute>
						</xsl:if>
				</xsl:when>
				
				<xsl:otherwise>
						<!-- <xsl:attribute name="class">obs rounded-corners</xsl:attribute> -->
						<xsl:attribute name="class">tabbertab</xsl:attribute>
						<xsl:attribute name="title">Obs. <xsl:value-of select="position()"/></xsl:attribute>
				</xsl:otherwise>
				</xsl:choose>
						
				<xsl:choose>
				<xsl:when test="$totalObs = 1">
				</xsl:when>
				<xsl:when test="$notCurrent = 1">
						<br/>
						<span class="bodyText" style="margin-left:10px;"><strong>Observation:</strong> <em>Preliminary / Draft Version</em></span>
				</xsl:when>
				<xsl:otherwise>
						<br/>
						<span class="bodyText" style="margin-left:10px;"><strong>Observation:</strong> <em><xsl:value-of select="oc:obs_metadata/oc:name"/></em></span>
				</xsl:otherwise>
				</xsl:choose>
				<div class="obsProps rounded-corners">
					<p class="subHeader"><xsl:if test="$notCurrent = 1"><xsl:attribute name="style">color: #7D5757;</xsl:attribute></xsl:if>	
									<xsl:value-of select="oc:var_label"/><xsl:if test="$notCurrent = 1">No Longer Current </xsl:if>Description (<xsl:value-of select="count(descendant::arch:properties/arch:property)"/> properties)</p>
					
					<table border="0" cellpadding="1">
						 <xsl:for-each select="arch:properties/arch:property">
							  <tr>
								<td width='95'>
									<xsl:if test="$notCurrent = 1">
										<xsl:attribute name="style">color: #716F6F;</xsl:attribute>
										</xsl:if>	
									<xsl:value-of select="oc:var_label"/>            </td>
								<td> </td>
								<td>
									<xsl:if test="$notCurrent = 1">
										<xsl:attribute name="style">color: #716F6F;</xsl:attribute>
										</xsl:if>
									<xsl:if test="oc:var_label/@type != 'alphanumeric'">
									<a>
										<xsl:attribute name="href"><xsl:value-of select="$propBaseURI"/><xsl:value-of select="oc:propid"/></xsl:attribute>
										<xsl:if test="$notCurrent = 1">
										<xsl:attribute name="style">color: #716F6F;</xsl:attribute>
										</xsl:if>
										<xsl:choose>
										<xsl:when test="contains(oc:show_val, 'http://')">
										(Outside Link)
										</xsl:when>
										<xsl:otherwise>
											<xsl:if test="oc:var_label/@type='calendar'">
													<xsl:value-of select="oc:show_val"/>
											</xsl:if>
											<xsl:if test="oc:var_label/@type != 'calendar'">
												<xsl:value-of select="oc:show_val"/>
											</xsl:if>
										</xsl:otherwise>
										</xsl:choose>
									</a>
									</xsl:if>
									<xsl:if test="oc:var_label/@type = 'alphanumeric'">
											<xsl:value-of select="oc:show_val"/>
									</xsl:if>
								</td>
								
							  </tr>
						  </xsl:for-each>
						  <xsl:if test="count(descendant::arch:properties/arch:property) = 0">
							<tr><td><xsl:value-of select="arch:spatialUnit/oc:metadata/oc:no_props"/></td></tr>
						  </xsl:if>
					</table>
				</div>
			
				<div class="allnotes bodyText">
					<p class="subHeader">Item Notes</p>
					<xsl:if test="count(descendant::arch:links/oc:diary_links/oc:link) != 0" >	
						<p class="bodyText">
							<xsl:for-each select="arch:spatialUnit/arch:observations/arch:observation/arch:links/oc:diary_links/oc:link">
							   <a><xsl:attribute name="href">../documents/<xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/></a><xsl:if test="position() != last()"> , </xsl:if>
							</xsl:for-each>
						</p>
					</xsl:if>
					<xsl:if test="$num_externalRefs != 0" >	
						<p class="bodyText"> 
							<xsl:for-each select="../oc:external_references/oc:reference">
							   <a><xsl:attribute name="href"><xsl:value-of select="oc:ref_URI"/></xsl:attribute><em><xsl:value-of select="oc:name" disable-output-escaping="yes"/></em></a>
							   <xsl:if test="position() != last()"> , </xsl:if>
							</xsl:for-each>
						</p>
				</xsl:if>
					
					
					<xsl:if test="count(descendant::arch:notes/arch:note) = 0" >
					<p class="bodyText">(This item has no additional notes)</p>
					</xsl:if>
					<xsl:for-each select="arch:notes/arch:note">
						<p class="bodyText"><xsl:value-of select="arch:string" disable-output-escaping="yes" /></p><br/>
					</xsl:for-each>
					<xsl:if test="$obsPos = 1">
					<p class="bodyText"><span style='text-decoration:underline;'>Suggested Citation:</span><br/><xsl:value-of select="$citationView"/></p>
				</xsl:if>
				</div>
			
				<xsl:if test="count(descendant::arch:links/oc:space_links/oc:link) != 0" >
					<div id="all_links">
						<p class="subHeader">Linked Items (<xsl:value-of select="count(descendant::arch:links/oc:space_links/oc:link)"/> items)</p>
							<xsl:for-each select="arch:links/oc:space_links/oc:link">
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
				
				
				<div>
				<xsl:choose>
				<xsl:when test="$totalObs= 1">
						<xsl:attribute name="id">all_people</xsl:attribute>
				</xsl:when>
				<xsl:otherwise>
						<xsl:attribute name="class">obsPeople bodyText</xsl:attribute>
				</xsl:otherwise>
				</xsl:choose>
						
		<p class="subHeader">Associated People (<xsl:value-of select="count(descendant::arch:links/oc:person_links/oc:link)"/> items)</p>
		<xsl:if test="count(descendant::arch:links/oc:person_links/oc:link) != 0" >	
			<p class="bodyText">
				<xsl:for-each select="arch:links/oc:person_links/oc:link">
				   <a><xsl:attribute name="href">../persons/<xsl:value-of select="oc:id"/></xsl:attribute><xsl:value-of select="oc:name"/></a> ( <xsl:value-of select="oc:relation"/> )<xsl:if test="position() != last()"> , </xsl:if>
				</xsl:for-each>
			</p>
		</xsl:if>
		<br/>
		<br/>	
	</div>
	
</div>      
</xsl:template>










</xsl:stylesheet>
