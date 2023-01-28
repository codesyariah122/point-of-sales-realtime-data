<template>
    <default-layout>
        <div class="p-5 mb-4 bg-light rounded-3">
            <div class="container-fluid py-5">
                <h1 class="display-5 fw-bold">C.O.D(O.T) Buah Import</h1>

                <p class="col-md-8 fs-4">
                    {{ context.user[0].address }} ({{
                        context.user[0].post_code
                    }})
                    {{ context.user[0].province }}
                </p>
                <button class="btn btn-primary btn-lg" type="button">
                    See Now !
                </button>
            </div>
        </div>

        <div class="row align-items-md-stretch">
            <div class="col-md-6">
                <div class="h-100 p-5 text-white bg-dark rounded-3">
                    <h2>Change the background</h2>
                    <p>
                        Swap the background-color utility and add a `.text-*`
                        color utility to mix up the jumbotron look. Then, mix
                        and match with additional component themes and more.
                    </p>
                    <button class="btn btn-outline-light" type="button">
                        Example button
                    </button>
                </div>
            </div>
            <div class="col-md-6">
                <div class="h-100 p-5 bg-light border rounded-3">
                    <h2>Add borders</h2>
                    <p>
                        Or, keep it light and add a border for some added
                        definition to the boundaries of your content. Be sure to
                        look under the hood at the source HTML here as we've
                        adjusted the alignment and sizing of both column's
                        content for equal-height.
                    </p>
                    <button class="btn btn-outline-secondary" type="button">
                        Example button
                    </button>
                </div>
            </div>
        </div>
    </default-layout>
</template>

<script>
import DefaultLayout from "../layouts/DefaultLayout";

export default {
    name: "Home",
    components: { DefaultLayout },
    mounted() {
        console.log($context);
    },
    data() {
        return {
            context: window?.context,
        };
    },
};
</script>
