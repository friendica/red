{{ for $threads as $item }}
{{ inc $item.template }}{{ endinc }}
{{ endfor }}

