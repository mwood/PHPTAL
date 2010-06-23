<?php

class CompressTest extends PHPTAL_TestCase
{
    private function assertStrips($expect,$source, $html5 = false)
    {
        $tpl = $this->newPHPTAL();
        if ($html5) $tpl->setOutputMode(PHPTAL::HTML5);
        $tpl->setPreFilter(new PHPTAL_PreFilter_Compress());

        $tpl->setSource($source);

        $this->assertEquals($expect,$tpl->execute());
    }

    function testConsecutiveSpace()
    {
        $this->assertStrips("<p>foo bar baz</p>",
        "<p>foo     \t bar\n\n\n baz</p>");
    }

    function testPreservesPre()
    {
        $this->assertStrips("<p>foo bar</p><pre>  \tfoo\t   \nbar   </pre>",
        "<p>foo   \t\n bar</p><pre>  \tfoo\t   \nbar   </pre>");
    }

    function testCase1() {
        $this->assertStrips('<p>foo <a href="">bar </a>baz</p>','<p>foo <a href=""> bar </a> baz  </p>');
    }
    function testCase2() {
        $this->assertStrips('<p>foo <a href="">bar </a>baz</p>', ' <p>foo <a href=""> bar </a>baz </p>');
    }
    function testCase3() {
        $this->assertStrips('<p>foo<a href=""> bar </a>baz</p>', ' <p> foo<a href=""> bar </a>baz </p>  ');
    }
    function testCase4() {
        $this->assertStrips('<p>foo <a href="">bar</a> baz</p>',' <p> foo <a href="">bar</a> baz</p>');
    }

    function testLastEmptyChild1() {
        $this->assertStrips('<p>foo<br/></p>', '<p>foo <br/></p>');
    }

    function testLastEmptyChild2() {
        $this->assertStrips('<p>foo<span></span></p>', '<p>foo <span></span></p>');
    }

    function testLastEmptyChild3() {
        $this->assertStrips('<p>foo<span></span></p>', '<p>foo <span></span> </p>');
    }

    function testLastEmptyChild4() {
        $this->assertStrips('<p>foo<!-- --></p>', '<p>foo <!-- --> </p>');
    }

    function testRespectsNamespace()
    {
        $this->assertStrips('<div>z<p xmlns="not:xhtml"> ke <br></br>ep </p>z</div>',
        '<div> z<p xmlns="not:xhtml"> ke <br></br> ep </p> z </div>');
    }

    function testStripsAllInHead()
    {
        $this->assertStrips('<html><head><title></title><link/><script>" ";</script><script></script><meta/><style></style></head></html>',
            '<html >
            <head > <title > </title > <link /> <script >" ";</script> <script/>
             <meta /> <style
              > </style >
               </head > </html>');
    }

    function testAdjacentBlocks()
    {
        $this->assertStrips('<div><p>test 123</p><p>456</p><ul><li>x</li></ul></div>','<div> <p> test 123 </p> <p> 456 </p> <ul> <li>x</li> </ul> </div>');
    }

    function testAdjacentBlocksPre()
    {
        $this->assertStrips('<div><p>test 123</p><pre> 456 </pre><p>x</p></div>','<div> <p> test 123 </p> <pre> 456 </pre> <p> x </p> </div>');
    }

    function testTalReplacedElementsAreText()
    {
        $this->assertStrips('<div>a x b</div>','<div> a <p tal:replace="string:x"/> b </div>');
    }

    /**
     * It's common to display list items with display:inline in horizontal menus
     */
    function testListItemsAreInline()
    {
        $this->assertStrips('<div><ul><li><a>a </a></li><li>b </li><li>c</li></ul></div>',
                          '<div> <ul> <li> <a> a </a> </li> <li> b </li> <li> c </li> </ul> </div>');
    }

    function testPreservesXMLSpace()
    {
         $this->assertStrips('<p>foo<span xml:space="preserve"> foo bar  baz </span> bla</p>',
                           '<p>  foo<span xml:space="preserve"> foo bar  baz </span> bla </p>');
    }

    function testRemovesInterelement()
    {
        $this->assertStrips('<table>x<tr>x<td>foo</td>x</tr>x</table>','<table> x <tr> x <td> foo </td> x </tr> x </table>');
        $this->assertStrips('<select>x<option></option>x<optgroup>x<option></option>x</optgroup>x</select>',
            '<select> x <option> </option> x <optgroup> x <option> </option> x </optgroup> x </select> ');
    }

    function testOrdersAttributes()
    {
        $this->assertStrips('<img src="foo" width="10" height="5" alt="x"/>','<img width="10" height="5" src="foo" alt="x" />');
    }

    function testSortsUnknownAttributes()
    {
        $this->assertStrips('<img alpha="1" beta="2" gamma="3"/>','<img gamma="3" alpha="1" beta="2" />');
    }

    function testPreFirstLine()
    {
        $this->assertStrips("<pre>\n\ntest</pre>", "<pre>\n\n\ntest</pre>");
    }

    function testDoesNotShortenXHTMLMeta()
    {
        $this->assertStrips('<meta content="text/plain;charset=UTF-8" http-equiv="Content-Type"/>',
        "<meta http-equiv='Content-Type' content='text/plain;charset=UTF-8'/>");
    }

    function testShortensHTML5Meta()
    {
        $this->assertStrips("<meta charset=utf-8>",
            "<meta http-equiv='Content-Type' content='text/plain;charset=UTF-8'/>",true);
    }

    function testShortensHTML5Types()
    {
        $this->assertStrips("<script></script><style></style>",
            "<script type='text/javascript ;charset=utf-8'
            language='javascript'></script><style type='text/css'></style>",true);
    }

    function testShortensHTML5TypesSafely()
    {
        $this->assertStrips('<script type="text/javascript;e4x=1"></script><script type="text/hack"></script>',
            '<script type="text/javascript;e4x=1"></script><script type="text/hack"></script>',true);
    }

    function testTalBlockInBlockIsBlock()
    {
        $this->assertStrips("<div>foobar</div>","<div> <tal:block> foo </tal:block> bar </div>");
    }

    function testTalBlockInInlineIsInline()
    {
        $this->assertStrips("<p><span>foo bar</span></p>","<p><span> <tal:block> foo </tal:block> bar </span></p>");
    }

    function testTalBlockInListIsInline()
    {
        $this->assertStrips("<ul><li>foo bar</li></ul>","<ul><li> <tal:block> foo </tal:block> bar </li></ul>");
    }

    function testPreservesSpaceBeforePI()
    {
        $this->assertStrips("<p>foo bar</p>","<p>foo <?php echo 'bar'; ?></p>");
    }

    function testAll()
    {
        $this->assertStrips("<html><head><title>Foo</title></head><body><p><a href=\"test\" title=\"x\">x </a>xu</p><br/>foo</body></html><!-- bla -->",

        '<html> <head> <title> Foo </title> </head>
        <body>
        <p>
        <a title="   x " href=" test "> x </a> xu
        </p>
        <br/>
        foo</body> </html>  <!-- bla -->');
    }
}