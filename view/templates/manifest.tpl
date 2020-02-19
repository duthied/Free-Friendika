{
  "name": "{{$title}}",
  "start_url": "{{$baseurl}}",
  "display": "standalone",
  "description": "{{$description}}",
{{if $background_color}}
  "theme_color": "{{$theme_color}}",
{{/if}}
{{if $background_color}}
  "background_color": "{{$background_color}}",
{{/if}}
  "short_name": "Friendica",
  "icons": [{
    "src": "{{$baseurl}}/{{$touch_icon}}",
    "sizes": "128x128",
    "type": "image/png"
  }]
}