<?xml version="1.0"?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
    <xsl:output method="html" indent="yes" encoding="US-ASCII" doctype-public="-//W3C//DTD HTML 4.01 Transitional//EN" />

    <xsl:template match="screenshots">
        <html>
            <link href="/screenshots.css" media="screen" rel="stylesheet" type="text/css"/>
            <title>YProx Screenshots</title>
            <body>
                <h1>YProx Screenshots - <xsl:value-of select="@date"/></h1>
                <xsl:for-each select="//site[@isBaseSite='yes']">
                    <xsl:call-template name="base-site"/>
                </xsl:for-each>
            </body>
        </html>
    </xsl:template>

    <xsl:template name="base-site">
        <div class="platform">
            <h2>Platform: <xsl:value-of select="@title"/></h2>
            <xsl:call-template name="site-screenshot"/>
            <xsl:for-each select="//site[ @dataParentId = current()/@id]">
                <xsl:call-template name="site-screenshot"/>
            </xsl:for-each>
        </div>
    </xsl:template>

    <xsl:template name="site-screenshot">
        <div class="screenshot">
            <xsl:if test="@isBaseSite='yes'">
                <xsl:attribute name="class">screenshot baseSite</xsl:attribute>
            </xsl:if>
            <h3>
                <xsl:value-of select="@title"/>
                <xsl:if test="@isBaseSite='yes'">
                    (BS)
                </xsl:if>
            </h3>

            <a>
                <xsl:attribute name="href">/original/<xsl:value-of select="../@id"/>.png</xsl:attribute>
                <img>
                    <xsl:attribute name="src">/400x400/<xsl:value-of select="../@id"/>.png</xsl:attribute>
                </img>
            </a>
            <table class="properties">
                <tr>
                    <th>Host</th>
                    <th>Status</th>
                </tr>
                <tr>
                    <td>
                        <a>
                            <xsl:attribute name="href">http://<xsl:value-of select="@host"/></xsl:attribute>
                            http://<xsl:value-of select="@host"/>
                        </a>
                    </td>
                    <td><xsl:value-of select="@billingStatus"/></td>
                </tr>
            </table>
            <table class="properties">
                <tr>
                    <th>Theme</th>
                    <th>Engine</th>
                    <th>ID</th>
                </tr>
                <tr>
                    <td><xsl:value-of select="theme/*/@title"/></td>
                    <td><xsl:value-of select="@themeEngine"/></td>
                    <td><xsl:value-of select="theme/*/@id"/></td>
                </tr>
            </table>
        </div>
    </xsl:template>
</xsl:stylesheet>

