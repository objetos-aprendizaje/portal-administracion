export class Trees {
    constructor(order, tree, selectedNodes = []) {
        this.order = order;
        this.tree = tree;
        this.selectedNodes = new Set(selectedNodes);
    }

    // Mapa estático para almacenar instancias
    static instances = new Map();

    // Método estático para crear y almacenar una instancia
    static storeInstance(order, tree, selectedNodes = []) {
        const instance = new Trees(order, tree, selectedNodes);
        console.log('new instance', instance)
        Trees.instances.set(order, instance); // Corregido: eliminar el tercer argumento
        return instance;
    }

    // Método estático para obtener una instancia por su order
    static getInstance(order) {
        return Trees.instances.get(order);
    }

    // Método para agregar un nodo seleccionado
    addSelectedNode(node) {
        this.selectedNodes.add(node);
    }

    // Método para obtener todos los nodos seleccionados
    getSelectedNodes() {
        return Array.from(this.selectedNodes);
    }

    // Método estático para agregar un nodo seleccionado por order
    static addSelectedNodeByOrder(order, node) {
        const instance = Trees.getInstance(order);
        if (instance) {
            instance.addSelectedNode(node);
        }
    }

    static addNodesSelectedByOrder(order, nodes) {
        const instance = Trees.getInstance(order);
        if (instance) {
            nodes.forEach((node) => instance.addSelectedNode(node));
        }
    }

    // Método estático para obtener nodos seleccionados por order
    static getSelectedNodesByOrder(order) {
        const instance = Trees.getInstance(order);
        if (instance) {
            return instance.getSelectedNodes();
        }
        return []; // Devolver un array vacío si no se encuentra la instancia
    }

    // Método para eliminar un nodo seleccionado
    deleteSelectedNode(node) {
        this.selectedNodes.delete(node);
    }

    // Método estático para eliminar un nodo seleccionado por order
    static deleteSelectedNodeByOrder(order, node) {
        const instance = Trees.getInstance(order);
        if (instance) {
            instance.deleteSelectedNode(node);
        }
    }

    // Método estático para eliminar varios nodos seleccionados por order
    static deleteSelectedNodesByOrder(order, nodes) {
        const instance = Trees.getInstance(order);
        if (instance) {
            nodes.forEach((node) => instance.deleteSelectedNode(node));
        }
    }
}
