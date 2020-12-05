function substr_count(haystack, needle) {
    return (haystack.match('/' + needle + '/g') || []).length;
}