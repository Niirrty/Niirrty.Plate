{{~ MyApp::Text1}}

{{~ MyApp::Text2}}

{{~ MyApp::Text3=This is a unknown translation value}}

Hilfe 1: {{? 1}}
Hilfe 2: {{? 8#MyAnchor}}
Hilfe 3: {{? 12}}

{{~ MyApp::TitleInt}} {{$int}}
{{~ MyApp::TitleFloat}} {{$float}}
{{~ MyApp::TitleBool}} {{if $bool}}TRUE{{else}}FALSE{{/if}}
{{+$Varname = 'Whatever 1'}}
TPL Var    : {{$Varname}}
{{* Single line comment *}}

Array 2

{{foreach from="$array2.0" key=key value=value}}
{{$key}}: {{$value}}
{{/foreach}}

{{# $subTemplateFile}}

Array 1

{{for from=$array1 index=i step=1}}
{{if ($i % 2) === 0}}*{{else}}-{{/if}} {{$array1.$i}}
{{/for}}

{{~ MyApp::TitleDtm}}: {{$datetime->format($formatDtm)}}