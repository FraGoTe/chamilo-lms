{% if pagination != '' %}
    {{ pagerfanta(pagination, 'twitter_bootstrap3', { 'proximity': 3 } ) }}
{% endif %}