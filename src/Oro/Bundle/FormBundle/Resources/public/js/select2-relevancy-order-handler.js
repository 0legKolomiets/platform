/*global define*/
define(['oroui/js/tools'], function(tools){
    /**
     * @export  oroform/js/select2-relevancy-order-handler
     * @class   oroform.select2RelevancyOrderHandler
     */
    return {
        handle: function(configs){
            if (configs['ajax']) {
                configs.minimumInputLength = 0;
                return;
            }

            configs['sortResults'] = function(results, container, query){
                if (!query.term || query.term.length < 1) {
                    return results;
                }
                var expression = tools.safeRegExp(query.term, 'im');

                var sortIteratorDelegate = function (first, second) {
                    var inFirst = first.text.search(expression);
                    var inSecond = second.text.search(expression);

                    if (inFirst == -1 || inSecond == -1) {
                        return inSecond - inFirst;
                    }

                    return inFirst - inSecond;
                };

                return results.sort(sortIteratorDelegate);
            }
        }
    };
});
